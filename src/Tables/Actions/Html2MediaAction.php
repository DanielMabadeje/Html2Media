<?php

namespace Torgodly\Html2Media\Tables\Actions;

// use Filament\Tables\Actions\Action;

use Closure;
use Filament\Actions\Action;
use Torgodly\Html2Media\Traits\HasHtml2MediaActionBase;

class Html2MediaAction extends Action
{
    use HasHtml2MediaActionBase;

    protected bool $requiresConfirmation = false;

    /**
     * FIXED: Override requiresConfirmation for table actions
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
     * FIXED: Element ID for table actions with proper record handling
     */
    public function getElementId(): string
    {
        // Always re-evaluate closures for table actions since record context changes
        if ($this->elementId instanceof \Closure) {
            $evaluated = $this->evaluate($this->elementId);
            return $evaluated ?: 'html2media-' . $this->getName() . '-' . uniqid();
        }

        // For static element IDs, use caching
        if ($this->resolvedElementId) {
            return $this->resolvedElementId;
        }

        $evaluated = $this->evaluate($this->elementId);
        
        if ($evaluated) {
            $this->resolvedElementId = $evaluated;
        } else {
            // Fallback with record ID if available
            $record = $this->getRecord();
            $recordId = $record?->getKey() ?? uniqid();
            $this->resolvedElementId = 'html2media-' . $this->getName() . '-' . $recordId;
        }

        return $this->resolvedElementId;
    }
}