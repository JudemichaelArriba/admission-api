<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function upload(Request $request, int $id)
    {
        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        if (!$this->canAccessApplicantDocuments($request, $applicant)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($applicant->status !== Applicant::STATUS_PENDING) {
            return response()->json(['message' => 'Documents can only be uploaded while application is pending'], 409);
        }

        $validated = $request->validate([
            'document_type' => 'required|string|max:50',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $uploaded = $request->file('file');
        $disk = 'local';
        $filePath = $uploaded->store('applicant_documents', $disk);

        $document = ApplicantDocument::create([
            'applicant_id' => $applicant->id,
            'document_type' => strtolower($validated['document_type']),
            'file_path' => $filePath,
            'disk' => $disk,
            'original_filename' => $uploaded->getClientOriginalName(),
            'mime_type' => $uploaded->getMimeType(),
            'file_size' => $uploaded->getSize(),
            'sha256' => hash_file('sha256', $uploaded->getRealPath()),
            'scan_status' => 'pending',
        ]);

        $this->logAudit($request, 'applicant_document_uploaded', 'applicant_document', $document->id, [
            'applicant_id' => $applicant->id,
            'document_type' => $document->document_type,
            'mime_type' => $document->mime_type,
        ]);

        return response()->json($document, 201);
    }

    public function index(Request $request, int $id)
    {
        $applicant = Applicant::with('documents')->find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        if (!$this->canAccessApplicantDocuments($request, $applicant)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($applicant->documents);
    }

    public function download(Request $request, int $id, int $documentId)
    {
        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        if (!$this->canAccessApplicantDocuments($request, $applicant)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $document = ApplicantDocument::where('applicant_id', $id)->find($documentId);
        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        if (!Storage::disk($document->disk)->exists($document->file_path)) {
            return response()->json(['message' => 'Stored file not found'], 404);
        }

        $this->logAudit($request, 'applicant_document_downloaded', 'applicant_document', $document->id, [
            'applicant_id' => $applicant->id,
        ]);

        return Storage::disk($document->disk)->download($document->file_path, $document->original_filename ?? null);
    }

    private function canAccessApplicantDocuments(Request $request, Applicant $applicant): bool
    {
        $user = $request->user();
        return $user->hasRole(UserRole::ADMIN)
            || ($user->hasRole(UserRole::APPLICANT) && $applicant->user_id === $user->id);
    }
}
