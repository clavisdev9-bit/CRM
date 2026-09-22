<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ContactValidationIndex;
use App\Http\Requests\ContactValidationRequest;
use App\Http\Requests\ContactLinkValidationRequest;
use App\Http\Resources\ContactResources;
use App\Http\Resources\ContactResourcesCollection;
use App\Models\Contact;
use App\Models\ContactType;

class ContactController extends Controller
{
    protected $Contact;

    public function __construct(Contact $Contact)
    {
        $this->Contact = $Contact;
    }

    /**
     * ======================================================
     * LIST (standalone + linked, digabung jadi satu tampilan)
     * ------------------------------------------------------
     * Sengaja pakai DB::table() + LEFT JOIN + COALESCE (bukan Eloquent
     * ->with()) supaya company_name/contact_name/email/phone milik
     * contact LINKED ikut bisa di-search & di-sort di level SQL, dan
     * tetap 1 query + 1 paginate (tidak N+1 walau tiap baris linked
     * butuh data dari tabel sumber yang beda-beda). Polanya mirip
     * Costumers::customers() yang juga heavy pakai leftJoin+subquery
     * untuk kasus serupa.
     *
     * Untuk baris STANDALONE: source_type/source_id NULL, jadi semua
     * LEFT JOIN sumber otomatis tidak match apa pun -> COALESCE jatuh
     * ke kolom company_name/dst milik contacts sendiri.
     * ======================================================
     */
    public function index(ContactValidationIndex $request)
    {
        $validated = $request->validated();

        $search        = $validated['search'] ?? null;
        $perPage       = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
        $sortBy        = $validated['sort_by'] ?? 'created_at';
        $sortDir       = $validated['sort_dir'] ?? 'desc';
        $contactTypeId = $validated['contact_type_id'] ?? null;
        $sourceType    = $validated['source_type'] ?? null;
        $status        = $validated['status'] ?? null;

        $query = DB::table('contacts as c')
            ->select([
                'c.id',
                'c.contact_type_id',
                'ct.name as contact_type_name',
                'ct.is_system as contact_type_is_system',
                'c.contact_code',
                'c.source_type',
                'c.source_id',

                DB::raw("COALESCE(c.company_name, src_customer.company_name, src_lead.company_name, src_cc_parent.company_name, src_bc_parent.branch_name) as company_name"),
                DB::raw("COALESCE(c.contact_name, src_customer.contact_name, src_lead.contact_name, src_cc.name, src_bc.name) as contact_name"),
                // ── c.email/c.phone sekarang jsonb (array, khusus contact
                // STANDALONE -- lihat migration change_contacts_email_phone_to_jsonb
                // & Contact::$casts). Postgres COALESCE tidak bisa campur tipe
                // jsonb dengan varchar (kolom email/phone milik customers/leads/
                // dst yang TETAP varchar tunggal), jadi diratakan dulu jadi TEXT
                // yang digabung koma lewat subquery -- HANYA untuk tampilan
                // listing/card ini. Data array yang sesungguhnya (untuk form
                // edit) tetap didapat dari endpoint show() (Eloquent, kena cast). ──
                DB::raw("COALESCE(
                    (SELECT string_agg(e, ', ') FROM jsonb_array_elements_text(c.email) AS e),
                    src_customer.email, src_lead.email, src_cc.email, src_bc.email
                ) as email"),
                DB::raw("COALESCE(
                    (SELECT string_agg(p, ', ') FROM jsonb_array_elements_text(c.phone) AS p),
                    src_customer.phone, src_lead.phone, src_cc.phone, src_bc.phone
                ) as phone"),
                DB::raw("COALESCE(c.address, src_customer.address, src_lead.address) as address"),
                DB::raw("COALESCE(src_customer.customer_code, src_cc_parent.customer_code, src_bc_parent.branch_code) as source_code"),
                DB::raw("CASE
                    WHEN c.source_type = 'customer_contact' THEN src_cc_parent.company_name
                    WHEN c.source_type = 'branch_contact' THEN src_bc_parent.branch_name
                    ELSE NULL
                END as parent_label"),

                'c.notes',
                'c.status',
                'c.created_by',
                'c.created_at',
                'c.updated_at',
            ])
            ->leftJoin('contact_types as ct', 'ct.id', '=', 'c.contact_type_id')
            ->leftJoin('customers as src_customer', function ($join) {
                $join->on('src_customer.id', '=', 'c.source_id')
                    ->where('c.source_type', '=', 'customer');
            })
            ->leftJoin('leads as src_lead', function ($join) {
                $join->on('src_lead.id', '=', 'c.source_id')
                    ->where('c.source_type', '=', 'lead');
            })
            ->leftJoin('customer_contacts as src_cc', function ($join) {
                $join->on('src_cc.id', '=', 'c.source_id')
                    ->where('c.source_type', '=', 'customer_contact');
            })
            ->leftJoin('customers as src_cc_parent', 'src_cc_parent.id', '=', 'src_cc.customer_id')
            ->leftJoin('branch_contacts as src_bc', function ($join) {
                $join->on('src_bc.id', '=', 'c.source_id')
                    ->where('c.source_type', '=', 'branch_contact');
            })
            ->leftJoin('customer_branches as src_bc_parent', 'src_bc_parent.id', '=', 'src_bc.branch_id')
            ->whereNull('c.deleted_at');

        if ($contactTypeId) {
            $query->where('c.contact_type_id', $contactTypeId);
        }

        if ($sourceType) {
            $query->where('c.source_type', $sourceType);
        }

        if ($status) {
            $query->where('c.status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('c.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('c.contact_name', 'ILIKE', "%{$search}%")
                    ->orWhere('c.contact_code', 'ILIKE', "%{$search}%")
                    ->orWhere('src_customer.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('src_lead.company_name', 'ILIKE', "%{$search}%")
                    ->orWhere('src_cc.name', 'ILIKE', "%{$search}%")
                    ->orWhere('src_bc.name', 'ILIKE', "%{$search}%");
            });
        }

        // sort_by sudah divalidasi ContactValidationIndex hanya boleh
        // salah satu dari: company_name | status | created_at -- ketiganya
        // sama-sama valid dipakai langsung sebagai alias hasil SELECT.
        $query->orderBy($sortBy, $sortDir);

        $results = $query->paginate($perPage);

        $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
        return ApiResponse::paginate(new ContactResourcesCollection($results), $message);
    }

    /**
     * ======================================================
     * DETAIL (Eloquent -- untuk linked contact, ContactResources yang
     * akan panggil Contact::resolveSource() supaya datanya live)
     * ======================================================
     */
    public function show(string $id)
    {
        $contact = Contact::with('contactType')->find($id);

        if (!$contact) {
            return ApiResponse::error('Contact not found', [
                'id' => ['Data with that ID is not available']
            ], 404);
        }

        return ApiResponse::success(new ContactResources($contact), 'Success, take the detailed Contact', 200);
    }

    /**
     * ======================================================
     * STORE STANDALONE (Principle, Competitor, dst -- diisi manual)
     * ======================================================
     */
    public function storeStandalone(ContactValidationRequest $request)
    {
        $data = $request->validated();

        try {
            $userId = auth()->user()->id_user;

            $errors = Contact::isDuplicate($data);
            if (!empty($errors)) {
                return ApiResponse::error('Validation failed', $errors, 400);
            }

            $contact = $this->Contact->create([
                'contact_type_id' => $data['contact_type_id'],
                'contact_code'    => Contact::generateContactCode(),
                'source_type'     => null,
                'source_id'       => null,
                'company_name'    => $data['company_name'],
                'contact_name'    => $data['contact_name'] ?? null,
                'email'           => $data['email'] ?? null,
                'phone'           => $data['phone'] ?? null,
                'address'         => $data['address'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'status'          => $data['status'] ?? 'Active',
                'created_by'      => $userId,
            ]);

            $contact->load('contactType');

            return ApiResponse::success(new ContactResources($contact), 'Success Create New Contact', 201);

        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to create contact (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while creating the contact.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ======================================================
     * UPDATE STANDALONE
     * ======================================================
     */
    public function updateStandalone(ContactValidationRequest $request, $id)
    {
        $data = $request->validated();

        $contact = Contact::find($id);

        if (!$contact) {
            return ApiResponse::error(
                'Contact with that ID was not found.',
                ['id' => ['Data not available.']],
                404
            );
        }

        // ── Contact hasil LINK datanya diambil live dari tabel sumber --
        // tidak boleh diedit langsung lewat endpoint ini. Ubah di data
        // sumber aslinya (Customer/Lead/dst), atau unlink dulu kalau
        // memang mau jadi standalone. ──
        if ($contact->source_type) {
            return ApiResponse::error(
                'Contact ini hasil link dari data lain, tidak bisa diedit langsung dari sini. Ubah datanya di sumber aslinya, atau unlink dulu.',
                ['source_type' => ['Linked contact cannot be edited directly.']],
                403
            );
        }

        try {
            $errors = Contact::isDuplicate($data, $id);
            if (!empty($errors)) {
                return ApiResponse::error('Validation failed', $errors, 400);
            }

            $contact->update([
                'contact_type_id' => $data['contact_type_id'],
                'company_name'    => $data['company_name'],
                'contact_name'    => $data['contact_name'] ?? null,
                'email'           => $data['email'] ?? null,
                'phone'           => $data['phone'] ?? null,
                'address'         => $data['address'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'status'          => $data['status'] ?? $contact->status,
            ]);

            $contact->load('contactType');

            return ApiResponse::success(new ContactResources($contact), 'Success Update Contact', 200);

        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to update contact (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to update contact', [
                'exception' => config('app.debug') ? $e->getMessage() : 'Please try again later'
            ], 500);
        }
    }

    /**
     * ======================================================
     * DESTROY (dipakai untuk STANDALONE maupun LINKED)
     * ------------------------------------------------------
     * Untuk baris linked, "delete" di sini artinya UNLINK saja -- yang
     * dihapus cuma baris pointer-nya di tabel contacts (soft delete),
     * data aslinya di Customer/Lead/dst SAMA SEKALI tidak tersentuh.
     * ======================================================
     */
    public function destroy(string $id)
    {
        try {
            $contact = $this->Contact->find($id);
            if (!$contact) {
                return ApiResponse::error('Contact with that ID was not found.', [
                    'id' => ['Data not available.']
                ], 404);
            }

            $wasLinked = (bool) $contact->source_type;

            $contact->delete();

            return ApiResponse::success(
                new ContactResources($contact),
                $wasLinked ? 'Contact berhasil di-unlink.' : 'Success Delete Contact',
                200
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete contact', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ======================================================
     * STORE LINK ("Link dari Data Existing")
     * ======================================================
     */
    public function storeLink(ContactLinkValidationRequest $request)
    {
        $data = $request->validated();

        try {
            $userId = auth()->user()->id_user;

            // ── Pastikan baris sumbernya BENAR-BENAR ada, pakai
            // resolveSource() yang sama persis dipakai buat nampilin
            // detail -- satu logic saja, tidak dobel. ──
            $probe = new Contact([
                'source_type' => $data['source_type'],
                'source_id'   => $data['source_id'],
            ]);

            $resolved = $probe->resolveSource();

            if (!$resolved) {
                return ApiResponse::error(
                    'Data sumber tidak ditemukan.',
                    ['source_id' => ['Data yang mau di-link sudah tidak ada atau sudah dihapus.']],
                    404
                );
            }

            // ── Cegah link dobel. Unique index di migration
            // (contacts_source_unique_idx) juga menjaga ini di level DB,
            // tapi dicek dulu di sini supaya errornya lebih ramah
            // daripada QueryException mentah. ──
            $alreadyLinked = Contact::where('source_type', $data['source_type'])
                ->where('source_id', $data['source_id'])
                ->exists();

            if ($alreadyLinked) {
                return ApiResponse::error(
                    'Data ini sudah pernah di-link sebagai Contact sebelumnya.',
                    ['source_id' => ['Already linked.']],
                    409
                );
            }

            $contactType = ContactType::findBySourceType($data['source_type']);

            if (!$contactType) {
                // ── Harusnya tidak pernah kejadian kalau migration
                // seed-nya jalan normal -- dijaga di sini cuma buat
                // pesan error yang jelas kalau somehow reserved type-nya
                // hilang/terhapus manual dari DB. ──
                return ApiResponse::error(
                    'Jenis contact reserved untuk source ini belum tersedia. Hubungi administrator.',
                    ['source_type' => ["No reserved contact_type for source_type '{$data['source_type']}'."]],
                    500
                );
            }

            $contact = $this->Contact->create([
                'contact_type_id' => $contactType->id,
                'contact_code'    => null, // linked contact tidak punya kode sendiri
                'source_type'     => $data['source_type'],
                'source_id'       => $data['source_id'],
                'company_name'    => null,
                'contact_name'    => null,
                'email'           => null,
                'phone'           => null,
                'address'         => null,
                'notes'           => $data['notes'] ?? null,
                'status'          => $data['status'] ?? 'Active',
                'created_by'      => $userId,
            ]);

            $contact->load('contactType');

            return ApiResponse::success(new ContactResources($contact), 'Success Link Contact', 201);

        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to link contact (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while linking the contact.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * ======================================================
     * SEARCH SOURCE OPTIONS
     * ------------------------------------------------------
     * Dipakai modal "Link dari Data Existing" di frontend buat nyari
     * kandidat data (Customer/Lead/Customer Contact/Branch Contact)
     * yang BELUM pernah di-link, sesuai source_type yang dipilih user.
     * Polanya mirip Costumers::searchCompany().
     * ======================================================
     */
    public function searchSourceOptions(Request $request)
    {
        try {
            $sourceType = $request->input('source_type');
            $search     = trim((string) $request->input('search', ''));

            if (!in_array($sourceType, Contact::SOURCE_TYPES, true)) {
                return ApiResponse::error('source_type tidak valid.', [
                    'source_type' => ['Invalid source_type. Allowed: ' . implode(', ', Contact::SOURCE_TYPES)]
                ], 422);
            }

            if (strlen($search) < 2) {
                return ApiResponse::success([], 'Keyword terlalu pendek');
            }

            // id-id yang sudah pernah di-link untuk source_type ini,
            // supaya tidak muncul lagi di hasil pencarian.
            $alreadyLinkedIds = Contact::where('source_type', $sourceType)
                ->pluck('source_id')
                ->all();

            $results = collect();

            switch ($sourceType) {

                case 'customer':
                    $results = DB::table('customers')
                        ->select([
                            'id',
                            'customer_code as code',
                            'company_name as label',
                            'contact_name',
                            'email',
                            'phone',
                        ])
                        ->whereNull('deleted_at')
                        ->where('approval_status', 'approved')
                        ->when(!empty($alreadyLinkedIds), fn ($q) => $q->whereNotIn('id', $alreadyLinkedIds))
                        ->where('company_name', 'ILIKE', "%{$search}%")
                        ->orderBy('company_name')
                        ->limit(15)
                        ->get();
                    break;

                case 'lead':
                    $results = DB::table('leads')
                        ->select([
                            'id',
                            DB::raw('NULL as code'),
                            'company_name as label',
                            'contact_name',
                            'email',
                            'phone',
                        ])
                        ->whereNull('deleted_at')
                        ->when(!empty($alreadyLinkedIds), fn ($q) => $q->whereNotIn('id', $alreadyLinkedIds))
                        ->where('company_name', 'ILIKE', "%{$search}%")
                        ->orderBy('company_name')
                        ->limit(15)
                        ->get();
                    break;

                case 'customer_contact':
                    $results = DB::table('customer_contacts as cc')
                        ->leftJoin('customers as c', 'c.id', '=', 'cc.customer_id')
                        ->select([
                            'cc.id',
                            'c.customer_code as code',
                            'cc.name as label',
                            'cc.email',
                            'cc.phone',
                            'c.company_name as parent_label',
                        ])
                        ->whereNull('cc.deleted_at')
                        ->when(!empty($alreadyLinkedIds), fn ($q) => $q->whereNotIn('cc.id', $alreadyLinkedIds))
                        ->where('cc.name', 'ILIKE', "%{$search}%")
                        ->orderBy('cc.name')
                        ->limit(15)
                        ->get();
                    break;

                case 'branch_contact':
                    $results = DB::table('branch_contacts as bc')
                        ->leftJoin('customer_branches as cb', 'cb.id', '=', 'bc.branch_id')
                        ->select([
                            'bc.id',
                            'cb.branch_code as code',
                            'bc.name as label',
                            'bc.email',
                            'bc.phone',
                            'cb.branch_name as parent_label',
                        ])
                        ->whereNull('bc.deleted_at')
                        ->when(!empty($alreadyLinkedIds), fn ($q) => $q->whereNotIn('bc.id', $alreadyLinkedIds))
                        ->where('bc.name', 'ILIKE', "%{$search}%")
                        ->orderBy('bc.name')
                        ->limit(15)
                        ->get();
                    break;
            }

            return ApiResponse::success(
                $results,
                $results->isEmpty() ? 'Data not found' : 'Success'
            );

        } catch (\Throwable $e) {
            return ApiResponse::error(
                'Failed search source options',
                config('app.debug') ? ['exception' => $e->getMessage()] : null,
                500
            );
        }
    }
}