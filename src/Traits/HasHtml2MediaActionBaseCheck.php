<?php

namespace Torgodly\Html2Media\Traits;

use Closure;
use Filament\Actions\Action;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;

trait HasHtml2MediaActionBaseCheck
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
    //     // return $this->evaluate($this->content);
    //     $content = $this->evaluate($this->content);

    //     if ($content) {
    //         return new Htmlable(
    //             '<div id="' . e($this->getElementId()) . '">' . $content->toHtml() . '</div>'
    //         );
    //     }

    //     return null;
    // }
    public function getContent(): ?Htmlable
    {
        $content = $this->evaluate($this->content);

        if (! $content) {
            return null;
        }

        if ($content instanceof Htmlable) {
            $html = $content->toHtml();
        } elseif ($content instanceof View) {
            $html = $content->render();
        } else {
            $html = (string) $content;
        }

        return new \Illuminate\Support\HtmlString(
            '<div id="' . e($this->getElementId()) . '">' . $html . '</div>'
        );
    }

    public function elementId(string|Closure $elementId = null): static
    {
        $this->elementId = $elementId;

        return $this;
    }

    public function getElementId(): string
{
    if ($this->resolvedElementId) {
        return $this->resolvedElementId;
    }

    $evaluated = $this->evaluate($this->elementId);

    if ($evaluated) {
        $this->resolvedElementId = $evaluated;
    } else {
        $this->resolvedElementId = 'html2media-' . uniqid();
    }

    return $this->resolvedElementId;
}

    /*
    |--------------------------------------------------------------------------
    | Filament Setup
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
        return [[
            'type' => $type ?? ($this->isSavePdf ? 'savePdf' : ($this->isPrint ? 'print' : null)),
            'element' => $this->getElementId(),
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
            ],
            'margin' => $this->getMargin(),
            'enableLinks' => $this->isEnableLinks(),
        ]];
    }

    public function shouldOpenModal(?Closure $checkForSchemaUsing = null): bool
    {
        return false;
    }
}
