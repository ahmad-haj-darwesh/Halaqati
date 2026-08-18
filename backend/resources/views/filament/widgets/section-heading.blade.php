<x-filament-widgets::widget>
    <div class="fi-dashboard-section-heading">
        <div class="fi-dashboard-section-heading-text">
            <h2>{{ $this->getTitle() }}</h2>
            @if ($this->getSubtitle())
                <p>{{ $this->getSubtitle() }}</p>
            @endif
        </div>
        <span class="fi-dashboard-section-heading-rule"></span>
    </div>
</x-filament-widgets::widget>
