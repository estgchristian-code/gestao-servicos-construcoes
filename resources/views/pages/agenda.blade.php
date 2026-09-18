<?php

use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $month = '';

    protected $queryString = ['month'];

    public function mount(): void
    {
        $this->authorize('viewAny', ServiceOrder::class);

        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $this->month)) {
            $this->month = now()->format('Y-m');
        }
    }

    #[Computed]
    public function orders()
    {
        $monthDate = $this->monthDate();

        $query = ServiceOrder::query()
            ->with(['client', 'technician'])
            ->forCompany(auth()->user())
            ->whereNotNull('service_orders.scheduled_at')
            ->whereBetween('service_orders.scheduled_at', [
                $monthDate->firstOfMonth()->format('Y-m-d'),
                $monthDate->endOfMonth()->format('Y-m-d'),
            ]);

        if (auth()->user()->isTecnico()) {
            $query->where('service_orders.technician_id', auth()->id());
        }

        return $query
            ->orderBy('service_orders.scheduled_at')
            ->get();
    }

    #[Computed]
    public function days(): array
    {
        $firstOfMonth = $this->monthDate()->firstOfMonth();
        $lastOfMonth = $this->monthDate()->endOfMonth();

        $byDay = $this->orders()->groupBy(fn (ServiceOrder $order) => $order->scheduled_at->format('Y-m-d'));

        $cursor = $firstOfMonth->startOfWeek(CarbonImmutable::SUNDAY);
        $end = $lastOfMonth->endOfWeek(CarbonImmutable::SUNDAY);

        $days = [];

        while ($cursor->lte($end)) {
            $days[] = [
                'date' => $cursor,
                'orders' => $byDay->get($cursor->format('Y-m-d'), collect()),
                'inMonth' => $cursor->month === $firstOfMonth->month && $cursor->year === $firstOfMonth->year,
            ];
            $cursor = $cursor->addDay();
        }

        return $days;
    }

    public function monthDate(): CarbonImmutable
    {
        [$year, $month] = array_map('intval', explode('-', $this->month));

        return CarbonImmutable::create($year, $month, 1);
    }

    public function monthLabel(): string
    {
        $names = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        $date = $this->monthDate();

        return $names[$date->month] . ' de ' . $date->year;
    }

    public function prevMonth(): void
    {
        $this->month = $this->monthDate()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->monthDate()->addMonth()->format('Y-m');
    }

    public function statusBorderClass(ServiceOrderStatus $status): string
    {
        return match ($status) {
            ServiceOrderStatus::Pending => 'border-slate-300',
            ServiceOrderStatus::Scheduled => 'border-blue-500',
            ServiceOrderStatus::InProgress => 'border-indigo-500',
            ServiceOrderStatus::Paused => 'border-amber-500',
            ServiceOrderStatus::Completed => 'border-emerald-500',
            ServiceOrderStatus::Cancelled => 'border-red-500',
        };
    }
};
?>
<div>
    @include('partials.flash')

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Agenda</h1>
            <p class="mt-1 text-sm text-slate-500">Organize as ordens de serviço agendadas por mês.</p>
        </div>

        <div class="flex flex-none items-center gap-2">
            <button type="button" wire:click="prevMonth" aria-label="Mês anterior"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>

            <p class="w-44 text-center text-lg font-semibold text-slate-900">{{ $this->monthLabel() }}</p>

            <button type="button" wire:click="nextMonth" aria-label="Próximo mês"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-7 gap-px overflow-hidden rounded-2xl bg-slate-200 shadow-sm ring-1 ring-slate-200">
        @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $dayName)
            <div class="bg-slate-50 px-3 py-2 text-center text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                {{ $dayName }}
            </div>
        @endforeach

        @foreach ($this->days as $day)
            <div class="{{ $day['inMonth'] ? 'bg-white' : 'bg-slate-50/50' }} min-h-[116px] p-2">
                <p class="{{ $day['inMonth'] ? 'text-slate-700' : 'text-slate-300' }} text-xs font-semibold">
                    {{ $day['date']->format('j') }}
                </p>

                <div class="mt-1.5 space-y-1.5">
                    @foreach ($day['orders'] as $order)
                        <a href="{{ route('service-orders.show', $order) }}"
                            class="block rounded-lg border-l-4 {{ $this->statusBorderClass($order->status) }} bg-white px-2.5 py-2 shadow-sm ring-1 ring-slate-200 transition hover:ring-indigo-300">
                            <div class="flex items-center justify-between gap-1">
                                <p class="truncate text-xs font-semibold text-slate-900">OS #{{ $order->number }}</p>
                                <span class="inline-flex flex-none items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 {{ $order->status->badgeClasses() }}">
                                    {{ $order->status->label() }}
                                </span>
                            </div>
                            <p class="mt-1 truncate text-[11px] text-slate-600">{{ $order->title }}</p>
                            <p class="truncate text-[11px] text-slate-500">{{ $order->client?->name ?? 'Sem cliente' }}</p>
                            <p class="truncate text-[11px] text-slate-400">{{ $order->technician?->name ?? 'Sem técnico' }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>