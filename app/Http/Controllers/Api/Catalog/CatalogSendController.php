<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Http\Requests\CatalogSendValidationIndex;
use App\Http\Requests\CatalogSendValidationRequest;
use App\Http\Resources\CatalogSendLogResources;
use App\Http\Resources\CatalogSendLogResourcesCollection;
use App\Mail\CatalogMail;
use App\Models\Catalog;
use App\Models\CatalogSendLog;
use App\Models\ProductCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CatalogSendController extends Controller
{
    protected $SendLog;

    public function __construct(CatalogSendLog $SendLog)
    {
        $this->SendLog = $SendLog;
    }

    // histori pengiriman (email & whatsapp)
    public function index(CatalogSendValidationIndex $request)
    {
        $validated = $request->validated();
        $search = $validated['search'] ?? null;
        $perPage = is_numeric($validated['per_page'] ?? null) ? $validated['per_page'] : 10;
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';
        $senderId = $validated['sender_id'] ?? null;
        $channel = $validated['channel'] ?? null;

        $query = $this->SendLog
            ->when($senderId, fn ($q) => $q->where('sender_id', $senderId))
            ->when($channel, fn ($q) => $q->where('channel', $channel))
            ->search($search)
            ->sort($sortBy, $sortDir);

        $results = $query->paginate($perPage);
        $message = $results->isEmpty() ? "Data yang Anda cari tidak ditemukan" : "Success";
        return ApiResponse::paginate(new CatalogSendLogResourcesCollection($results), $message);
    }

    // Kirim catalog via Email (beneran mengirim lewat SMTP, lalu dicatat)
    public function sendEmail(CatalogSendValidationRequest $request)
    {
        $data = $request->validated();

        $product = ProductCatalog::find($data['product_id']);
        if (!$product) {
            return ApiResponse::error('Product not found', [
                'product_id' => ['Data with that ID is not available']
            ], 404);
        }

        $catalog = !empty($data['catalog_id']) ? Catalog::find($data['catalog_id']) : null;

        $sender = Auth::user();

        $status = 'sent';
        $errorMessage = null;

        try {
            // ── Kalau catalog-nya berupa file upload (khususnya PDF),
            // lampirkan langsung sebagai attachment. Untuk youtube/vimeo/
            // external_link, link-nya cukup ditaruh di body email
            // (lihat catalog-send.blade.php). ──
            $attachmentPath = null;
            if ($catalog && $catalog->source_type === 'upload' && Storage::disk('public')->exists($catalog->url)) {
                $attachmentPath = Storage::disk('public')->path($catalog->url);
            }

            Mail::to($data['recipient_email'])->send(new CatalogMail($product, $catalog, $attachmentPath));
        } catch (\Exception $e) {
            $status = 'failed';
            $errorMessage = config('app.debug') ? $e->getMessage() : 'Gagal mengirim email. Silakan coba lagi.';
        }

        $log = $this->SendLog->create([
            'catalog_id'      => $catalog?->id,
            'product_id'      => $product->id,
            'sender_id'       => $sender?->id_user,
            'sender_name'     => $sender?->name,
            'sender_email'    => $sender?->email,
            'recipient_name'  => $data['recipient_name'] ?? null,
            'recipient_email' => $data['recipient_email'],
            'recipient_phone' => null,
            'product_name'    => $product->name,
            'catalog_title'   => $catalog?->title,
            'channel'         => 'email',
            'status'          => $status,
            'error_message'   => $errorMessage,
        ]);

        if ($status === 'failed') {
            return ApiResponse::error('Gagal mengirim catalog via email.', [
                'email' => [$errorMessage ?? 'Terjadi kesalahan saat mengirim email.']
            ], 500);
        }

        return ApiResponse::success(new CatalogSendLogResources($log), 'Success Send Catalog via Email', 201);
    }

    // Catat pengiriman via WhatsApp. Pengiriman aktualnya terjadi di
    // sisi client (buka link wa.me dengan pesan siap kirim) -- endpoint
    // ini HANYA mencatat histori, tidak mengirim apapun dari server.
    public function logWhatsapp(CatalogSendValidationRequest $request)
    {
        $data = $request->validated();

        $product = ProductCatalog::find($data['product_id']);
        if (!$product) {
            return ApiResponse::error('Product not found', [
                'product_id' => ['Data with that ID is not available']
            ], 404);
        }

        $catalog = !empty($data['catalog_id']) ? Catalog::find($data['catalog_id']) : null;

        $sender = Auth::user();

        try {
            $log = $this->SendLog->create([
                'catalog_id'      => $catalog?->id,
                'product_id'      => $product->id,
                'sender_id'       => $sender?->id_user,
                'sender_name'     => $sender?->name,
                'sender_email'    => $sender?->email,
                'recipient_name'  => $data['recipient_name'] ?? null,
                'recipient_email' => null,
                'recipient_phone' => $data['recipient_phone'],
                'product_name'    => $product->name,
                'catalog_title'   => $catalog?->title,
                'channel'         => 'whatsapp',
                'status'          => 'sent',
                'error_message'   => null,
            ]);

            return ApiResponse::success(new CatalogSendLogResources($log), 'Success Log Catalog Send via WhatsApp', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Failed to log whatsapp send', [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}