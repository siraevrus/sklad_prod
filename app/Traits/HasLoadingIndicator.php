<?php

namespace App\Traits;

trait HasLoadingIndicator
{
    /**
     * Override the create method to add loading indicator functionality
     */
    public function create(bool $another = false): void
    {
        // Dispatch event to show loading indicator
        $this->dispatch('formSubmitting');

        // Call parent create method
        parent::create($another);

        // Dispatch event to hide loading indicator
        $this->dispatch('formSubmitted');
    }

    /**
     * Override the edit method to add loading indicator functionality
     */
    public function save(bool $another = false): void
    {
        // Dispatch event to show loading indicator
        $this->dispatch('formSubmitting');

        // Call parent save method
        parent::save($another);

        // Dispatch event to hide loading indicator
        $this->dispatch('formSubmitted');
    }
}
