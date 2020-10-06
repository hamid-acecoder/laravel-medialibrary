<?php

namespace Spatie\MediaLibrary\MediaCollections\Models\Concerns;

trait CustomMediaProperties
{
    public function setCustomHeaders(array $customHeaders): self
    {
        $this->setCustomProperty('custom_headers', $customHeaders);

        return $this;
    }

    public function getCustomHeaders()
    {
        return $this->getCustomProperty('custom_headers', []);
    }
}
