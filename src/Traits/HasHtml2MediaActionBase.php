<?php

namespace Torgodly\Html2Media\Traits;

use Filament\Actions\MountableAction;

trait HasHtml2MediaActionBase
{
    protected bool $isPrint = true;
    protected bool $isSavePdf = true;

    public function isPrint(): bool
    {
        return $this->isPrint;
    }

    public function isSavePdf(): bool
    {
        return $this->isSavePdf;
    }

    public function disablePrint(): static
    {
        $this->isPrint = false;

        return $this;
    }

    public function disableSavePdf(): static
    {
        $this->isSavePdf = false;

        return $this;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->modalHeading(fn (): string => $this->getLabel());
        $this->modalSubmitAction(false);

        $this->action(fn (MountableAction $action) =>
            !$this->shouldOpenModal()
                ? $action->getLivewire()->dispatch(
                    'triggerPrint',
                    ...$this->getDispatchOptions()
                )
                : null
        );
    }

    protected function getDispatchOptions(string $type = null): array
    {
        $options = [
            'elementId' => $this->getElementId(),
        ];

        if ($type) {
            $options['type'] = $type;
        }

        return [$options];
    }

    protected function shouldOpenModal(): bool
    {
        return false; // adjust if you want modal support later
    }
}
