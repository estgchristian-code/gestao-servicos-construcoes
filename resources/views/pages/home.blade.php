<?php

use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public int $openCount = 0;

    public int $scheduledCount = 0;

    public int $inProgressCount = 0;

    public int $pausedCount = 0;

    public int $completedCount = 0;

    public int $cancelledCount = 0;

    public int $todayCount = 0;

    public int $maxStatusCount = 0;

    /** @var array<string, int> */
    public array $statusCounts = [];

    public function mount(): void
    {
        $query = $this->scopedQuery();

        $rows = (clone $query)
            ->selectRaw('service_orders.status, COUNT(*) as total')
            ->groupBy('service_orders.status')
            ->pluck('total', 'status');

        $counts = [];
        foreach (ServiceOrderStatus::cases() as $status) {
            $counts[$status->value] = (int) ($rows[$status->value] ?? 0);
        }

        $this->statusCounts = $counts;
        $this->maxStatusCount = max($counts);
        $this->scheduledCount = $counts['scheduled'];
        $this->inProgressCount = $counts['in_progress'];
        $this->pausedCount = $counts['paused'];
        $this->completedCount = $counts['completed'];
        $this->cancelledCount = $counts['cancelled'];
        $this->openCount = $counts['pending'] + $counts['scheduled'] + $counts['in_progress'] + $counts['paused'];

        $this->todayCount = (clone $query)
            ->whereDate('service_orders.scheduled_at', now()->toDateString())
            ->count();
    }

    #[Computed]
    public function upcoming()
    {
        return $this->scopedQuery()
            ->whereNotNull('service_orders.scheduled_at')
            ->where('service_orders.scheduled_at', '>=', now()->toDateString())
            ->whereNotIn('service_orders.status', ['completed', 'cancelled'])
            ->with(['client'])
            ->orderBy('service_orders.scheduled_at')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function latest()
    {
        return $this->scopedQuery()
            ->with(['client'])
            ->orderByDesc('service_orders.created_at')
            ->take(5)
            ->get();
    }

    protected function scopedQuery(): Builder
    {
        $user = auth()->user();

        $query = ServiceOrder::query()->forCompany($user);

        if ($user->isTecnico()) {
            $query->where('service_orders.technician_id', $user->id);
        }

        return $query;
    }

    public function statusBarColor(ServiceOrderStatus $status): string
    {
        return match ($status) {
            ServiceOrderStatus::Pending => 'bg-slate-400',
            ServiceOrderStatus::Scheduled => 'bg-blue-500',
            ServiceOrderStatus::InProgress => 'bg-indigo-500',
            ServiceOrderStatus::Paused => 'bg-amber-500',
            ServiceOrderStatus::Completed => 'bg-emerald-500',
            ServiceOrderStatus::Cancelled => 'bg-red-500',
        };
    }
};
?>
<div>
    @include('partials.flash')

    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">{{ auth()->user()->company?->name ?? 'Sua empresa' }} — resumo operacional das ordens de serviço.</p>
    </div>

    @php
        $statCards = [
            ['label' => 'OS abertas', 'value' => $this->openCount, 'accent' => 'bg-indigo-500', 'hint' => 'Pendentes, agendadas, em execução e pausadas'],
            ['label' => 'Agendadas', 'value' => $this->scheduledCount, 'accent' => 'bg-blue-500', 'hint' => 'Aguardando início'],
            ['label' => 'Em execução', 'value' => $this->inProgressCount, 'accent' => 'bg-indigo-500', 'hint' => 'Iniciadas no momento'],
            ['label' => 'Pausadas', 'value' => $this->pausedCount, 'accent' => 'bg-amber-500', 'hint' => 'Interrompidas temporariamente'],
            ['label' => 'Concluídas', 'value' => $this->completedCount, 'accent' => 'bg-emerald-500', 'hint' => 'Finalizadas'],
            ['label' => 'Canceladas', 'value' => $this->cancelledCount, 'accent' => 'bg-red-500', 'hint' => 'Sem seguimento'],
            ['label' => 'Para hoje', 'value' => $this->todayCount, 'accent' => 'bg-teal-500', 'hint' => 'Agendadas para ' . now()->format('d/m/Y')],
        ];

        $statusRows = collect(ServiceOrderStatus::cases())->map(function (ServiceOrderStatus $status) {
            $count = $this->statusCounts[$status->value] ?? 0;

            return [
                'status' => $status,
                'count' => $count,
                'pct' => $this->maxStatusCount > 0 ? round($count / $this->maxStatusCount * 100) : 0,
            ];
        });
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($statCards as $card)
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 flex-none rounded-full {{ $card['accent'] }}"></span>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                </div>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Próximas OS agendadas</h2>
                <p class="mt-0.5 text-xs text-slate-500">As próximas ordens de serviço agendadas a partir de hoje.</p>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($this->upcoming as $order)
                    <a href="{{ route('service-orders.show', $order) }}"
                        class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 transition hover:bg-slate-50/70">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-sm font-semibold text-slate-900">OS #{{ $order->number }}</p>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium ring-1 {{ $order->status->badgeClasses() }}">
                                    {{ $order->status->label() }}
                                </span>
                            </div>
                            <p class="mt-0.5 truncate text-sm text-slate-600">{{ $order->title }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-400">{{ $order->client?->name ?? 'Sem cliente' }}</p>
                        </div>
                        <p class="flex-none text-sm font-medium text-slate-500">{{ $order->scheduled_at?->format('d/m/Y') ?? '—' }}</p>
                    </a>
                @empty
                    <div class="px-6 py-12 text-center">
                        <p class="text-sm font-semibold text-slate-700">Nenhuma OS agendada pela frente</p>
                        <p class="mt-1 text-sm text-slate-500">As próximas ordens de serviço aparecerão aqui.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-semibold text-slate-900">OS por status</h2>
                <p class="mt-0.5 text-xs text-slate-500">Quantidade de ordens de serviço em cada situação.</p>
            </div>

            <div class="space-y-4 px-6 py-5">
                @foreach ($statusRows as $row)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-3">
                            <p class="text-sm font-medium text-slate-600">{{ $row['status']->label() }}</p>
                            <p class="text-sm font-semibold text-slate-900">{{ $row['count'] }}</p>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full {{ $this->statusBarColor($row['status']) }}" style="width: {{ $row['pct'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-6 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Últimas ordens de serviço</h2>
            <p class="mt-0.5 text-xs text-slate-500">As ordens de serviço mais recentes da {{ strtolower(auth()->user()->roleLabel() === 'Técnico' ? 'sua' : 'empresa') }}.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                <thead class="bg-slate-50/70">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Número</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Título</th>
                        <th scope="col" class="hidden px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 md:table-cell">Cliente</th>
                        <th scope="col" class="hidden px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 sm:table-cell">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Criada em</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->latest as $order)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="px-6 py-4">
                                <a href="{{ route('service-orders.show', $order) }}" class="font-medium text-slate-900 hover:text-indigo-600">
                                    OS #{{ $order->number }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <p class="truncate text-slate-600">{{ $order->title }}</p>
                            </td>
                            <td class="hidden px-6 py-4 md:table-cell">
                                <p class="truncate text-slate-600">{{ $order->client?->name ?? '—' }}</p>
                            </td>
                            <td class="hidden px-6 py-4 sm:table-cell">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $order->status->badgeClasses() }}">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-slate-500">
                                {{ $order->created_at->format('d/m/Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <p class="text-sm font-medium text-slate-700">Nenhuma ordem de serviço ainda</p>
                                <p class="mt-1 text-sm text-slate-500">Crie uma ordem de serviço para ver o resumo aqui.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>