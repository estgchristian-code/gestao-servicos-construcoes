<?php

use App\Models\ServiceOrder;
use App\Models\ServiceOrderAttachment;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ServiceOrder $order;

    public $file;

    public function mount(ServiceOrder $order): void
    {
        $this->order = $order;

        $this->authorize('view', $this->order);
    }

    #[Computed]
    public function canViewAttachments(): bool
    {
        $user = auth()->user();

        if (! $user->belongsToCompany($this->order->company)) {
            return false;
        }

        if ($user->isAdmin() || $user->isComercial()) {
            return true;
        }

        return $user->isTecnico()
            && $this->order->technician_id !== null
            && (int) $this->order->technician_id === $user->id;
    }

    #[Computed]
    public function canManage(): bool
    {
        return auth()->user()->can('create', [ServiceOrderAttachment::class, $this->order]);
    }

    #[Computed]
    public function attachments()
    {
        return $this->order->attachments()->with('user')->get();
    }

    public function save(): void
    {
        $this->authorize('create', [ServiceOrderAttachment::class, $this->order]);

        $this->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
        ], [
            'file.required' => 'Selecione um arquivo para enviar.',
            'file.max' => 'O arquivo não pode ter mais de 10 MB.',
            'file.mimes' => 'Somente imagens (JPG, PNG, WEBP) e PDF são permitidos.',
        ]);

        $attachment = new ServiceOrderAttachment([
            'user_id' => auth()->id(),
            'name' => $this->file->getClientOriginalName(),
            'path' => $this->file->store('attachments', 'local'),
            'mime_type' => $this->file->getMimeType(),
            'size' => $this->file->getSize(),
        ]);
        $attachment->service_order_id = $this->order->id;
        $attachment->company_id = $this->order->company_id;
        $attachment->save();

        unset($this->attachments);
        $this->reset('file');
        $this->resetValidation();
        $this->dispatch('attachment-uploaded');
        session()->flash('status', 'Anexo enviado com sucesso.');
    }

    public function remove(int $attachmentId): void
    {
        $attachment = $this->order->attachments()->findOrFail($attachmentId);

        $this->authorize('delete', $attachment);

        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        unset($this->attachments);
        $this->dispatch('attachment-uploaded');
        session()->flash('status', 'Anexo excluído com sucesso.');
    }

    public function formatSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / 1024 / 1024, 2, ',', '') . ' MB';
        }

        return number_format($bytes / 1024, 1, ',', '') . ' KB';
    }
};
?>
<div class="space-y-4">
    @if ($this->canManage)
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Enviar foto ou anexo</h2>
                <p class="mt-0.5 text-xs text-slate-500">Imagens (JPG, PNG, WEBP) ou PDF de até 10 MB.</p>
            </div>

            <div class="px-6 py-5">
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <input type="file" wire:model="file"
                            accept="image/jpeg,image/png,image/webp,application/pdf"
                            class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                        @error('file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                        Enviar anexo
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($this->canViewAttachments)
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Fotos e anexos</h2>
                <p class="mt-0.5 text-xs text-slate-500">Arquivos relacionados à execução desta ordem de serviço.</p>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($this->attachments as $attachment)
                <div class="flex flex-wrap items-center gap-4 px-6 py-4">
                    @if (str_starts_with($attachment->mime_type, 'image/'))
                        <a href="{{ route('service-orders.attachments.show', $attachment) }}" target="_blank"
                            class="block h-16 w-16 flex-none overflow-hidden rounded-lg ring-1 ring-slate-200">
                            <img src="{{ route('service-orders.attachments.show', $attachment) }}" alt="{{ $attachment->name }}"
                                class="h-full w-full object-cover">
                        </a>
                    @else
                        <div class="flex h-16 w-16 flex-none items-center justify-center rounded-lg bg-red-50 ring-1 ring-red-100">
                            <span class="text-xs font-bold text-red-600">PDF</span>
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-800">{{ $attachment->name }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ $this->formatSize($attachment->size) }} ·
                            {{ $attachment->user?->name ?? 'Usuário removido' }} ·
                            {{ $attachment->created_at->format('d/m/Y \à\s H:i') }}
                        </p>
                    </div>

                    <div class="flex flex-none items-center gap-2">
                        <a href="{{ route('service-orders.attachments.show', $attachment) }}" target="_blank"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                            Visualizar
                        </a>
                        <a href="{{ route('service-orders.attachments.show', ['attachment' => $attachment, 'download' => 1]) }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                            Baixar
                        </a>
                        @if (auth()->user()->can('delete', $attachment))
                            <button type="button" wire:click="remove({{ $attachment->id }})"
                                wire:confirm="Excluir este anexo?"
                                class="inline-flex items-center rounded-lg bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 ring-1 ring-red-100 transition hover:bg-red-100">
                                Excluir
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <p class="text-sm font-bold text-slate-700">Nenhum anexo enviado ainda</p>
                    <p class="mt-1 text-sm text-slate-500">Fotos e arquivos da execução aparecerão aqui.</p>
                </div>
            @endforelse
        </div>
    </div>
@endif
</div>