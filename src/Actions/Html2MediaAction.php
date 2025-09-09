<?php

namespace Torgodly\Html2Media\Actions;

use Closure;
use Filament\Actions\Action;
use Torgodly\Html2Media\Traits\HasHtml2MediaActionBase;

class Html2MediaAction extends Action
{
    use HasHtml2MediaActionBase;

    protected bool $requiresConfirmation = false;

    /**
     * FIXED: Override requiresConfirmation to work with our modal logic
     */
    public function requiresConfirmation(bool|Closure $condition = true): static
    {
        $this->requiresConfirmation = $condition;
        return parent::requiresConfirmation($condition);
    }

    /**
     * FIXED: Check if confirmation is required
     */
    protected function shouldConfirm(): bool
    {
        return $this->requiresConfirmation;
    }

    /**
     * FIXED: Better element ID handling for actions
     */
    public function getElementId(): string
    {
        // Always re-evaluate closures to get current context
        if ($this->elementId instanceof \Closure) {
            return $this->evaluate($this->elementId) ?: 'html2media-' . $this->getName() . '-' . uniqid();
        }

        if ($this->resolvedElementId) {
            return $this->resolvedElementId;
        }

        $evaluated = $this->evaluate($this->elementId);
        $this->resolvedElementId = $evaluated ?: 'html2media-' . $this->getName() . '-' . uniqid();

        return $this->resolvedElementId;
    }
}