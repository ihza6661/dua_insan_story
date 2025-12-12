<?php

namespace App\Services;

use App\Models\DigitalInvitation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Service for exporting digital invitations to PDF and images.
 */
class ExportService
{
    /**
     * Generate PDF for a digital invitation.
     *
     * @param DigitalInvitation $invitation
     * @param array $options PDF generation options
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePdf(DigitalInvitation $invitation, array $options = []): \Barryvdh\DomPDF\PDF
    {
        // Load invitation with relationships
        $invitation->load(['template', 'data']);

        // Prepare data for the view
        $viewData = $this->prepareViewData($invitation);

        // Merge export settings from template
        $exportSettings = $invitation->template->export_settings ?? [];
        $options = array_merge($this->getDefaultPdfOptions(), $exportSettings, $options);

        // Generate HTML from template
        $html = $this->generateHtmlForExport($invitation, $viewData, $options);

        // Create PDF instance
        $pdf = Pdf::loadHTML($html);

        // Apply PDF options
        $pdf->setPaper($options['paper_size'] ?? 'a4', $options['orientation'] ?? 'portrait');
        
        // Set DPI for better image quality
        if (isset($options['dpi'])) {
            $pdf->setOption('dpi', $options['dpi']);
        }

        return $pdf;
    }

    /**
     * Export invitation as PDF stream (for download).
     *
     * @param DigitalInvitation $invitation
     * @param string|null $filename
     * @return \Illuminate\Http\Response
     */
    public function downloadPdf(DigitalInvitation $invitation, ?string $filename = null): \Illuminate\Http\Response
    {
        $pdf = $this->generatePdf($invitation);
        
        $filename = $filename ?? $this->generateFilename($invitation, 'pdf');

        return $pdf->download($filename);
    }

    /**
     * Save PDF to storage and return path.
     *
     * @param DigitalInvitation $invitation
     * @param string|null $filename
     * @return string Storage path
     */
    public function savePdf(DigitalInvitation $invitation, ?string $filename = null): string
    {
        $pdf = $this->generatePdf($invitation);
        
        $filename = $filename ?? $this->generateFilename($invitation, 'pdf');
        $path = "exports/{$invitation->id}/{$filename}";

        // Save to storage
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Prepare view data from invitation.
     *
     * @param DigitalInvitation $invitation
     * @return array
     */
    protected function prepareViewData(DigitalInvitation $invitation): array
    {
        $data = $invitation->data;
        $customizationJson = $data->customization_json ?? [];
        $customFields = $customizationJson['custom_fields'] ?? [];

        return [
            'invitation' => $invitation,
            'template' => $invitation->template,
            'data' => $data,
            'bride_name' => $data->bride_name,
            'groom_name' => $data->groom_name,
            'event_date' => $data->event_date,
            'event_time' => $data->event_time,
            'venue_name' => $data->venue_name,
            'venue_address' => $data->venue_address,
            'venue_maps_url' => $data->venue_maps_url,
            'opening_message' => $data->opening_message,
            'photo_paths' => $data->photo_paths ?? [],
            'hero_photo' => $data->hero_photo,
            'gallery_photos' => $data->gallery_photos,
            'custom_fields' => $customFields,
            'color_scheme' => $data->color_scheme,
            'is_export' => true, // Flag to indicate this is export mode
        ];
    }

    /**
     * Generate HTML for PDF/image export.
     *
     * @param DigitalInvitation $invitation
     * @param array $viewData
     * @param array $options
     * @return string
     */
    protected function generateHtmlForExport(DigitalInvitation $invitation, array $viewData, array $options): string
    {
        $templateComponent = $invitation->template->template_component;

        // Check if a custom export view exists for this template
        $viewName = "exports.templates.{$templateComponent}";
        
        if (!View::exists($viewName)) {
            // Fallback to generic export template
            $viewName = 'exports.invitation';
        }

        // Add export-specific options to view data
        $viewData['export_options'] = $options;
        $viewData['show_watermark'] = $options['show_watermark'] ?? false;

        return View::make($viewName, $viewData)->render();
    }

    /**
     * Generate filename for export.
     *
     * @param DigitalInvitation $invitation
     * @param string $extension
     * @return string
     */
    protected function generateFilename(DigitalInvitation $invitation, string $extension): string
    {
        $data = $invitation->data;
        
        // Try to create a meaningful filename
        $parts = [];
        
        if ($data->bride_name && $data->groom_name) {
            $parts[] = Str::slug($data->bride_name);
            $parts[] = Str::slug($data->groom_name);
        } else {
            $parts[] = $invitation->slug;
        }

        $filename = implode('-', $parts);
        
        return "invitation-{$filename}.{$extension}";
    }

    /**
     * Get default PDF options.
     *
     * @return array
     */
    protected function getDefaultPdfOptions(): array
    {
        return [
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'dpi' => 150, // Higher DPI for better image quality
            'show_watermark' => false,
            'include_qr_code' => true, // Include QR code to invitation URL
            'enable_remote' => false, // Security: Don't load remote images
        ];
    }

    /**
     * Get PDF metadata for invitation.
     *
     * @param DigitalInvitation $invitation
     * @return array
     */
    public function getPdfMetadata(DigitalInvitation $invitation): array
    {
        $data = $invitation->data;

        return [
            'title' => $data->bride_name && $data->groom_name 
                ? "Undangan {$data->bride_name} & {$data->groom_name}"
                : "Undangan Digital",
            'author' => 'Dua Insan Story',
            'subject' => 'Digital Wedding Invitation',
            'keywords' => 'wedding, invitation, digital',
        ];
    }

    /**
     * Validate if invitation can be exported.
     *
     * @param DigitalInvitation $invitation
     * @return bool
     * @throws \Exception
     */
    public function validateForExport(DigitalInvitation $invitation): bool
    {
        // Check if invitation has required data
        if (!$invitation->data) {
            throw new \Exception('Invitation data is missing.');
        }

        // Check if template exists
        if (!$invitation->template) {
            throw new \Exception('Template not found.');
        }

        // Check if template is active
        if (!$invitation->template->is_active) {
            throw new \Exception('Template is not active.');
        }

        return true;
    }

    /**
     * Get export statistics for invitation.
     *
     * @param DigitalInvitation $invitation
     * @return array
     */
    public function getExportStats(DigitalInvitation $invitation): array
    {
        // Check if exports directory exists
        $exportPath = "exports/{$invitation->id}";
        $files = Storage::disk('public')->files($exportPath);

        return [
            'total_exports' => count($files),
            'last_export_at' => count($files) > 0 
                ? Storage::disk('public')->lastModified($files[0]) 
                : null,
            'export_files' => array_map(function ($file) {
                return [
                    'path' => $file,
                    'url' => asset('storage/' . $file),
                    'size' => Storage::disk('public')->size($file),
                    'modified_at' => Storage::disk('public')->lastModified($file),
                ];
            }, $files),
        ];
    }
}
