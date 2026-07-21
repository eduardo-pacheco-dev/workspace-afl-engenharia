<?php

namespace App\Livewire\Stations;

use App\Models\Station;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.app.sidebar')]
#[Title('Station Details')]
class StationShow extends Component
{
    use WithFileUploads;

    public ?Station $station = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newAttachments = [];

    public bool $showPreviewModal = false;
    public string $previewUrl = '';
    public string $previewFilename = '';
    public string $previewMime = '';

    public string $newComment = '';

    public function mount(int $id): void
    {
        $this->station = Station::with(['attachments', 'comments.user'])->findOrFail($id);
    }

    public function saveAttachments(): void
    {
        $this->validate([
            'newAttachments.*' => ['file', 'max:10240'],
        ]);

        foreach ($this->newAttachments as $file) {
            $path = $file->store('stations', 'public');
            $this->station->attachments()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->newAttachments = [];
        $this->station->load('attachments');
        Flux::toast(variant: 'success', text: __('Attachments uploaded successfully.'));
    }

    public function removeAttachment(int $attachmentId): void
    {
        $attachment = $this->station->attachments()->find($attachmentId);
        if ($attachment) {
            Storage::disk('public')->delete($attachment->path);
            $attachment->delete();
            $this->station->load('attachments');
            Flux::toast(variant: 'success', text: __('Attachment deleted successfully.'));
        }
    }

    public function openPreview(string $url, string $filename, string $mime): void
    {
        $this->previewUrl = $url;
        $this->previewFilename = $filename;
        $this->previewMime = $mime;
        $this->showPreviewModal = true;
    }

    public function addComment(): void
    {
        $this->validate([
            'newComment' => ['required', 'string', 'max:5000'],
        ]);

        $this->station->comments()->create([
            'user_id' => auth()->id(),
            'body' => $this->newComment,
        ]);

        $this->newComment = '';
        $this->station->load('comments.user');
        Flux::toast(variant: 'success', text: __('Comment added successfully.'));
    }

    public function deleteComment(int $commentId): void
    {
        $comment = $this->station->comments()->find($commentId);
        if ($comment && $comment->user_id === auth()->id()) {
            $comment->delete();
            $this->station->load('comments.user');
            Flux::toast(variant: 'success', text: __('Comment deleted successfully.'));
        }
    }

    public function render()
    {
        return view('livewire.stations.station-show', [
            'statuses' => Station::statuses(),
            'types' => Station::types(),
        ]);
    }
}
