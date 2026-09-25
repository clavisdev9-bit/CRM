<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\Notificationvalidationindex;
use App\Http\Requests\NotificationValidationRequest;
use App\Http\Resources\NotificationResources;
use App\Http\Resources\NotificationResourcesCollection;
use App\Http\Resources\NotificationRecipientResources;
use App\Http\Resources\NotificationRecipientResourcesCollection;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ── NotificationController -- Phase A (Bulk & Reminder). Agenda
 * Notification (Phase B) menyusul setelah modul Agenda existing
 * di-link, lihat catatan di store().
 *
 * CATATAN PERMISSION: project ini tidak punya pola role-check di level
 * backend controller (dicek di ContactController/ProductCatalogController
 * dkk, semuanya juga tidak ada) -- pembatasan "siapa boleh apa" selama
 * ini murni ditegakkan lewat menu/permission access di frontend
 * (ms_access_menu/ms_access_submenu, usePermissionStore()). Mengikuti
 * pola yang sama, store() di sini TIDAK menge-cek role user di backend.
 * Kalau mau diperketat (mis. Sales tidak boleh hit endpoint ini sama
 * sekali walau tahu URL-nya), itu perubahan terpisah -- beri tahu saya
 * kalau mau ditambahkan. ──
 */
class NotificationController extends Controller
{
    protected $Notification;
    protected $NotificationRecipient;
    protected NotificationDispatcher $Dispatcher;

    public function __construct(Notification $Notification, NotificationRecipient $NotificationRecipient, NotificationDispatcher $Dispatcher)
    {
        $this->Notification = $Notification;
        $this->NotificationRecipient = $NotificationRecipient;
        $this->Dispatcher = $Dispatcher;
    }

    // ═══════════════════════════════════════════
    // ADMIN/MANAGER SIDE -- daftar notifikasi yang PERNAH DIBUAT
    // ═══════════════════════════════════════════
    public function index(NotificationValidationIndex $request)
    {
        $validated = $request->validated();
        $search = $validated['search'] ?? null;
        $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $category = $validated['category'] ?? null;
        $mine = filter_var($validated['mine'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $query = $this->Notification
            ->with('creator:id_user,fullname')
            ->withCount('recipients')
            ->category($category)
            ->when($mine, fn ($q) => $q->where('created_by', Auth::user()->id_user))
            ->search($search)
            ->sort($sortBy, $sortDir);

        $results = $query->paginate($perPage);
        $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
        return ApiResponse::paginate(new NotificationResourcesCollection($results), $message);
    }

    public function show(string $id)
    {
        $notification = $this->Notification->with('creator:id_user,fullname')->withCount('recipients')->find($id);
        if (!$notification) {
            return ApiResponse::error('Notification not found', [
                'id' => ['Data with that ID is not available']
            ], 404);
        }
        return ApiResponse::success(new NotificationResources($notification), 'Success', 200);
    }

    // ═══════════════════════════════════════════
    // RECIPIENT SIDE -- "notifikasi saya" (lonceng & Notification Center)
    // ═══════════════════════════════════════════
    public function myNotifications(NotificationValidationIndex $request)
    {
        $validated = $request->validated();
        $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
        $isRead = $validated['is_read'] ?? null;

        $userId = Auth::user()->id_user;

        $query = $this->NotificationRecipient
            ->with('notification')
            ->forUser($userId)
            ->when($isRead !== null, fn ($q) => $q->where('is_read', filter_var($isRead, FILTER_VALIDATE_BOOLEAN)))
            ->sort('created_at', 'desc');

        $results = $query->paginate($perPage);
        $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
        return ApiResponse::paginate(new NotificationRecipientResourcesCollection($results), $message);
    }

    public function unreadCount()
    {
        $userId = Auth::user()->id_user;

        $count = $this->NotificationRecipient
            ->forUser($userId)
            ->unread()
            ->count();

        return ApiResponse::success(['unread_count' => $count], 'Success', 200);
    }

    public function markAsRead(string $id)
    {
        $userId = Auth::user()->id_user;

        $recipient = $this->NotificationRecipient->forUser($userId)->find($id);
        if (!$recipient) {
            return ApiResponse::error('Notification not found', [
                'id' => ['Data with that ID is not available']
            ], 404);
        }

        if (!$recipient->is_read) {
            $recipient->update(['is_read' => true, 'read_at' => now()]);
        }

        return ApiResponse::success(new NotificationRecipientResources($recipient), 'Success Mark as Read', 200);
    }

    public function markAllAsRead()
    {
        $userId = Auth::user()->id_user;

        $this->NotificationRecipient
            ->forUser($userId)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return ApiResponse::success(null, 'Success Mark All as Read', 200);
    }

    // ═══════════════════════════════════════════
    // CREATE (Bulk & Reminder)
    // ═══════════════════════════════════════════
    public function store(NotificationValidationRequest $request)
    {
        $data = $request->validated();
        $sender = Auth::user();

        try {
            $payload = [
                'category'       => $data['category'],
                'title'          => $data['title'],
                'message'        => $data['message'],
                'target'         => $data['target'] ?? null,
                'recipient_ids'  => ($data['target'] ?? null) === 'specific' ? $data['recipient_ids'] : null,
                'reminder_type'  => $data['reminder_type'] ?? null,
                'reminder_scope' => $data['reminder_scope'] ?? null,
                'day_of_week'    => $data['day_of_week'] ?? null,
                'day_of_month'   => $data['day_of_month'] ?? null,
                'time_of_day'    => $data['time_of_day'] ?? null,
                'scheduled_at'   => $data['scheduled_at'] ?? null,
                'created_by'     => $sender->id_user,
            ];

            if ($data['category'] === 'bulk') {
                // ── reminder_scope/type/target 'personal' murni milik
                // reminder, dikosongkan paksa untuk bulk ──
                $payload['status'] = !empty($data['scheduled_at']) ? 'scheduled' : 'draft';
            } else {
                // reminder
                $payload['status'] = 'active';
            }

            $notification = $this->Notification->create($payload);

            if ($notification->category === 'reminder') {
                $notification->next_run_at = $this->Dispatcher->computeNextRunAt($notification);
                $notification->save();
                // ── TIDAK langsung bikin notification_recipients di
                // sini -- itu baru dibuat scheduler command
                // (ProcessDueNotifications) begitu next_run_at-nya
                // beneran jatuh tempo. Reminder "scheduled" pun sama,
                // fire sekali waktu next_run_at == scheduled_at tercapai. ──
            } elseif ($notification->category === 'bulk' && empty($data['scheduled_at'])) {
                // ── bulk tanpa scheduled_at = kirim SEKARANG ──
                $this->Dispatcher->deliverNow($notification);
            }
            // bulk DENGAN scheduled_at di masa depan -> menunggu
            // scheduler command (status tetap 'scheduled').

            return ApiResponse::success(new NotificationResources($notification->fresh(['creator'])), 'Success Create Notification', 201);
        } catch (\Illuminate\Database\QueryException $e) {
            return ApiResponse::error('Failed to create notification (query error)', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 422);
        } catch (\Exception $e) {
            return ApiResponse::error('An error occurred while creating the notification.', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function toggleReminderStatus(string $id)
    {
        $notification = $this->Notification->where('category', 'reminder')->find($id);
        if (!$notification) {
            return ApiResponse::error('Reminder not found', [
                'id' => ['Data with that ID is not available']
            ], 404);
        }

        $notification->status = $notification->status === 'active' ? 'inactive' : 'active';

        // ── nyalain lagi reminder yang sempat dimatikan -- hitung ulang
        // next_run_at dari sekarang, supaya tidak langsung fire beruntun
        // buat semua jadwal yang terlewat waktu dia mati. ──
        if ($notification->status === 'active') {
            $notification->next_run_at = $this->Dispatcher->computeNextRunAt($notification);
        }

        $notification->save();

        return ApiResponse::success(new NotificationResources($notification), 'Success Toggle Reminder Status', 200);
    }

    // ── Dropdown pilih penerima manual (target = 'specific'). Query
    // sama persis dengan Administrator::selectManager() (id_user,
    // fullname, hanya user aktif) -- dibuat method sendiri di sini
    // (bukan reuse endpoint selectManager yang sudah ada) supaya
    // Notification tidak bergantung ke rute Administrator yang mungkin
    // di-scope beda (mis. exclude_id logic khusus form User).
    //
    // company_id (opsional, query param) -- filter user per company
    // (group_companies), dipakai bareng dropdown Company di frontend
    // yang datanya diambil dari selectCompanies() di bawah. ──
    public function selectUsers(Request $request)
    {
        $companyId = $request->query('company_id');

        $users = \App\Models\MsUsers::query()
            ->select('id_user', 'fullname', 'group_id')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->when($companyId, fn ($q) => $q->where('group_id', $companyId))
            ->orderBy('fullname', 'asc')
            ->get();

        return ApiResponse::success($users, 'Success', 200);
    }

    // ── Dropdown pilih Company -- query langsung ke tabel
    // group_companies, sama seperti Administrator::selectGroup(), tapi
    // method sendiri di sini dengan alasan yang sama seperti selectUsers()
    // di atas (tidak bergantung ke rute Administrator). ──
    public function selectCompanies()
    {
        $companies = DB::table('group_companies')
            ->select('id_group', 'name_group')
            ->where('is_active', true)
            ->orderBy('name_group', 'asc')
            ->get();

        return ApiResponse::success($companies, 'Success', 200);
    }

    public function destroy(string $id)
    {
        try {
            $notification = $this->Notification->find($id);
            if (!$notification) {
                return ApiResponse::error('Notification not found', [
                    'id' => ['Data not available.']
                ], 404);
            }

            // ── cascadeOnDelete di FK notification_recipients.notification_id
            // otomatis ikut hapus semua histori recipient-nya. ──
            $notification->delete();
            return ApiResponse::success(null, 'Success Delete Notification', 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to delete notification', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

}