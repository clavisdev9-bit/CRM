<?php

namespace App\Mail;

use App\Models\Catalog;
use App\Models\ProductCatalog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * ── Mailable pengiriman Product Catalog ke customer/lead. Dibuat baru
 * mengikuti API Mailable modern Laravel (envelope/content/attachments)
 * -- contoh Mailable yang sudah ada di project belum sempat dibagikan,
 * jadi kalau mau samakan gaya persis dengan Mailable existing
 * (mis. ForgotPassword), silakan dibandingkan & disesuaikan. ──
 */
class CatalogMail extends Mailable
{
    use Queueable, SerializesModels;

    public ProductCatalog $product;
    public ?Catalog $catalog;
    public ?string $attachmentPath;

    public function __construct(ProductCatalog $product, ?Catalog $catalog = null, ?string $attachmentPath = null)
    {
        $this->product = $product;
        $this->catalog = $catalog;
        $this->attachmentPath = $attachmentPath;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Catalog Produk: ' . $this->product->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.catalog-send',
            with: [
                'product' => $this->product,
                'catalog' => $this->catalog,
            ],
        );
    }

    public function attachments(): array
    {
        if (!$this->attachmentPath || !file_exists($this->attachmentPath)) {
            return [];
        }

        return [
            Attachment::fromPath($this->attachmentPath)
                ->as(($this->catalog->title ?: $this->product->name) . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}