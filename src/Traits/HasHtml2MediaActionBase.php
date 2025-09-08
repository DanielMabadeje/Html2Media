<?php

namespace Torgodly\Html2Media\Traits;

use Closure;
use Filament\Actions\Action;

trait HasHtml2MediaActionBase
{
    protected bool $isPrint = false;
    protected bool $isSavePdf = false;
    protected bool $isPreview = false;

    /*
    |--------------------------------------------------------------------------
    | State getters
    |--------------------------------------------------------------------------
    */
    public function isPrint(): bool
    {
        return $this->isPrint;
    }

    public function isSavePdf(): bool
    {
        return $this->isSavePdf;
    }

    public function isPreview(): bool
    {
        return $this->isPreview;
    }

    /*
    |--------------------------------------------------------------------------
    | Fluent setters
    |--------------------------------------------------------------------------
    */
    public function enablePrint(bool $condition = true): static
    {
        $this->isPrint = $condition;

        return $this;
    }

    public function enableSavePdf(bool $condition = true): static
    {
        $this->isSavePdf = $condition;

        return $this;
    }

    public function enablePreview(bool $condition = true): static
    {
        $this->isPreview = $condition;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Core setup
    |--------------------------------------------------------------------------
    */
    public function setUp(): void
    {
        parent::setUp();

        $this->modalHeading(fn(): string => $this->getLabel());
        $this->modalSubmitAction(false);

        $this->action(function (Action $action) {
            if (! $this->shouldOpenModal()) {
                $action->getLivewire()->dispatch(
                    'triggerPrint',
                    ...$this->getDispatchOptions()
                );
            }
        });
    }

    protected function getDispatchOptions(?string $type = null): array
    {
        $options = [
            'elementId' => $this->getElementId(),
        ];

        if ($type) {
            $options['type'] = $type;
        }

        return [$options];
    }

    public function shouldOpenModal(?Closure $checkForSchemaUsing = null): bool
    {
        return false; // keep disabled until modal schema support is added
    }
}
