<?php

namespace Torgodly\Html2Media\Traits;

use Closure;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

trait HasHtml2MediaActionBase
{
    protected View|Htmlable|Closure|null $content = null;

    protected bool $isPrint = false;
    protected bool $isSavePdf = false;
    protected bool $isPreview = false;

    protected string|Closure $filename = 'document.pdf';
    protected array|Closure $pagebreak = ['mode' => ['css', 'legacy'], 'after' => 'section'];
    protected string|Closure $orientation = 'portrait';
    protected string|array|Closure $format = 'a4';
    protected string|Closure $unit = 'mm';
    protected int|Closure $scale = 2;
    protected int|Closure|array $margin = 0;
    protected bool|Closure $enableLinks = false;
    protected null|string|Closure $elementId = null;

    // 🔑 Cache for resolved element ID
    protected ?string $resolvedElementId = null;

    /*
    |--------------------------------------------------------------------------
    | Enable / Disable toggles
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
    | Config builders
    |--------------------------------------------------------------------------
    */
    public function filename(string|Closure $filename = 'document.pdf'): static
    {
        $this->filename = $filename;
        return $this;
    }

    public function getFilename(): string
    {
        $filename = $this->evaluate($this->filename);
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        return $baseName . '.pdf';
    }

    public function pagebreak(string|Closure|null $after = 'section', array|Closure|null $mode = ['css', 'legacy']): static
    {
        $this->pagebreak = ['mode' => $mode, 'after' => $after];
        return $this;
    }

    public function getPageBreak(): array
    {
        return $this->evaluate($this->pagebreak);
    }

    public function orientation(string|Closure|null $orientation = 'portrait'): static
    {
        $this->orientation = $orientation;
        return $this;
    }

    public function getOrientation(): string
    {
        return $this->evaluate($this->orientation);
    }

    public function format(string|array|Closure|null $format = 'a4', string|Closure|null $unit = 'mm'): static
    {
        $this->format = $format;
        $this->unit = $unit;
        return $this;
    }

    public function getFormat(): string|array
    {
        return $this->evaluate($this->format);
    }

    public function getUnit(): string
    {
        return $this->evaluate($this->unit);
    }

    public function scale(int|Closure|null $scale = 2): static
    {
        $this->scale = $scale;
        return $this;
    }

    public function getScale(): int
    {
        return $this->evaluate($this->scale);
    }

    public function margin(int|Closure|array|null $margin = 0): static
    {
        $this->margin = $margin;
        return $this;
    }

    public function getMargin(): int|array
    {
        return $this->evaluate($this->margin);
    }

    public function enableLinks(bool|Closure $enableLinks = true): static
    {
        $this->enableLinks = $enableLinks;
        return $this;
    }

    public function isEnableLinks(): bool
    {
        return $this->evaluate($this->enableLinks);
    }

    public function content(View|Htmlable|Closure|null $content = null): static
    {
        $this->content = $content;
        return $this;
    }

    // public function getContent(): ?Htmlable
    // {
    //     $content = $this->evaluate($this->content);

    //     if (!$content) {
    //         return null;
    //     }

    //     if ($content instanceof Htmlable) {
    //         $html = $content->toHtml();
    //     } elseif ($content instanceof View) {
    //         $html = $content->render();
    //     } else {
    //         $html = (string) $content;
    //     }

    //     return new \Illuminate\Support\HtmlString(
    //         '<div id="' . e($this->getElementId()) . '">' . $html . '</div>'
    //     );
    // }
    public function getContent(): ?Htmlable
{
    $content = $this->evaluate($this->content);
    if (!$content) {
        return null;
    }
    if ($content instanceof Htmlable) {
        $html = $content->toHtml();
    } elseif ($content instanceof View) {
        $html = $content->render();
    } else {
        $html = (string) $content;
    }
    $elementId = $this->getElementId();
    if (app()->hasDebugModeEnabled()) {
        logger()->info('Rendering content with ID', ['element_id' => $elementId]);
    }
    return new \Illuminate\Support\HtmlString(
        '<div id="' . e($elementId) . '" class="w-full max-w-none">' . $html . '</div>'
    );
}

    public function elementId(string|Closure $elementId = null): static
    {
        $this->elementId = $elementId;
        // Reset cached value when element ID is changed
        $this->resolvedElementId = null;
        return $this;
    }

    /**
     * FIXED: This method now properly handles record context and caching
     */
    public function getElementId(): string
    {
        // Always re-evaluate for dynamic contexts (like table actions with records)
        if ($this->elementId instanceof Closure) {
            return $this->evaluate($this->elementId) ?: 'html2media-' . uniqid();
        }

        // Use cached value for static element IDs
        if ($this->resolvedElementId) {
            return $this->resolvedElementId;
        }

        $evaluated = $this->evaluate($this->elementId);
        $this->resolvedElementId = $evaluated ?: 'html2media-' . uniqid();

        return $this->resolvedElementId;
    }

    /*
    |--------------------------------------------------------------------------
    | Filament Setup
    |--------------------------------------------------------------------------
    */
    // public function setUp(): void
    // {
    //     parent::setUp();

    //     $this->modalHeading(fn(): string => $this->getLabel());
    //     $this->modalSubmitAction(false);

    //     // FIXED: Only dispatch when modal is actually shown and content is rendered
    //     $this->action(function (Action $action) {
    //         if (!$this->shouldOpenModal()) {
    //             // For non-modal actions, dispatch immediately
    //             $action->getLivewire()->dispatch(
    //                 'triggerPrint',
    //                 ...$this->getDispatchOptions()
    //             );
    //         }
    //     });

    //     // FIXED: Add modal content with proper element ID
    //     $this->modalContent(function () {
    //         return $this->getContent();
    //     });

    //     // FIXED: Add modal footer actions for print/save when modal is used
    //     if ($this->shouldOpenModal()) {
    //         $this->modalFooterActions($this->getModalFooterActions());
    //     }
    // }

    public function setUp(): void
{
    parent::setUp();
    $this->modalHeading(fn(): string => $this->getLabel());
    $this->modalSubmitAction(false);
    $this->modalContent(function () {
        return $this->getContent();
    });
    if ($this->shouldOpenModal()) {
        $this->modalFooterActions($this->getModalFooterActions());
        $this->action(function (Action $action) {
            $options = $this->getDispatchOptions();
            $action->getLivewire()->dispatch('triggerPrint', ...$options);
            if (app()->hasDebugModeEnabled()) {
                logger()->info('Modal action triggered', ['options' => $options]);
            }
        });
    }
}
    /**
     * FIXED: Generate modal footer actions
     */
    public function getModalFooterActions(): array
    {
        $actions = [];

        if ($this->isPrint()) {
            // $actions[] = \Filament\Actions\Action::make('modal_print')
            //     ->label('Print')
            //     ->icon('heroicon-o-printer')
            //     ->action(function (Action $action) {
            //         $action->getLivewire()->dispatch(
            //             'triggerPrint',
            //             ...$this->getDispatchOptions('print')
            //         );
            //         // Close modal after dispatching
            //         $action->getLivewire()->mountAction(null);
            //     });

            $actions[] = \Filament\Actions\Action::make('modal_print')
    ->label('Print')
    ->icon('heroicon-o-printer')
    ->action(function (Action $action) {
        $options = $this->getDispatchOptions('print');
        $action->getLivewire()->dispatch('triggerPrint', ...$options);
        if (app()->hasDebugModeEnabled()) {
            logger()->info('Print action dispatched', ['options' => $options]);
        }
        $action->getLivewire()->mountAction(null); // Close modal after dispatch
    });
        }

        if ($this->isSavePdf()) {
            $actions[] = \Filament\Actions\Action::make('modal_save_pdf')
                ->label('Save as PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (Action $action) {
                    $action->getLivewire()->dispatch(
                        'triggerPrint',
                        ...$this->getDispatchOptions('savePdf')
                    );
                    if (app()->hasDebugModeEnabled()) {
            logger()->info('Print action dispatched', ['options' => $options]);
        }
                    // Close modal after dispatching
                    $action->getLivewire()->mountAction(null);
                });
        }

        if ($this->isPreview()) {
            $actions[] = \Filament\Actions\Action::make('modal_preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->action(function (Action $action) {
                    $action->getLivewire()->dispatch(
                        'triggerPrint',
                        ...$this->getDispatchOptions('preview')
                    );
                });
        }

        return $actions;
    }

    // protected function getDispatchOptions(?string $type = null): array
    // {
    //     $elementId = $this->getElementId();

    //     $options = [[
    //         'type' => $type ?? ($this->isSavePdf() ? 'savePdf' : ($this->isPrint() ? 'print' : null)),
    //         'element' => $elementId,
    //         'filename' => $this->getFilename(),
    //         'pagebreak' => $this->getPageBreak(),
    //         'jsPDF' => [
    //             'orientation' => $this->getOrientation(),
    //             'format' => $this->getFormat(),
    //             'unit' => $this->getUnit(),
    //         ],
    //         'html2canvas' => [
    //             'scale' => $this->getScale(),
    //             'useCORS' => true,
    //             'allowTaint' => false,
    //             'letterRendering' => true,
    //         ],
    //         'margin' => $this->getMargin(),
    //         'enableLinks' => $this->isEnableLinks(),
    //     ]];

    //     if (app()->hasDebugModeEnabled()) {
    //         logger()->info('Html2Media Dispatch Options', [
    //             'element_id' => $elementId,
    //             'options' => $options
    //         ]);
    //     }

    //     return $options;
    // }

    protected function getDispatchOptions(?string $type = null): array
    {
        $elementId = $this->getElementId(); // e.g., print-smart-content-123
        $options = [[
            'action' => $type ?? ($this->isSavePdf() ? 'savePdf' : ($this->isPrint() ? 'print' : null)),
            'element' => str_replace('print-smart-content-', '', $elementId), // Extract record ID or uniqid
            'filename' => $this->getFilename(),
            'pagebreak' => $this->getPageBreak(),
            'jsPDF' => [
                'orientation' => $this->getOrientation(),
                'format' => $this->getFormat(),
                'unit' => $this->getUnit(),
            ],
            'html2canvas' => [
                'scale' => $this->getScale(),
                'useCORS' => true,
                'allowTaint' => false,
                'letterRendering' => true,
            ],
            'margin' => $this->getMargin(),
            'enableLinks' => $this->isEnableLinks(),
        ]];
        if (app()->hasDebugModeEnabled()) {
            logger()->info('Html2Media Dispatch Options', [
                'element_id' => $elementId,
                'options' => $options
            ]);
        }
        return $options;
    }

    /**
     * FIXED: Only open modal when preview is enabled or explicitly needed
     */
    public function shouldOpenModal(?Closure $checkForSchemaUsing = null): bool
    {
        // Open modal if preview is enabled, or if requiresConfirmation is set
        return $this->isPreview() || $this->shouldConfirm();
    }

    /**
     * Check if the action requires confirmation
     */
    protected function shouldConfirm(): bool
    {
        // This method should be overridden in child classes if needed
        // or check if requiresConfirmation() was called
        return property_exists($this, 'requiresConfirmation') && $this->requiresConfirmation === true;
    }
}
