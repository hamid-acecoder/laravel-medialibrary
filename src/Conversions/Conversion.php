<?php

namespace Spatie\MediaLibrary\Conversions;

use BadMethodCallException;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/** @mixin \Spatie\Image\Manipulations */
class Conversion
{
    protected string $name = '';

    protected ConversionFileNamer $conversionFileNamer;

    protected float $extractVideoFrameAtSecond = 0;

    protected Manipulations $manipulations;

    protected array $performOnCollections = [];

    protected bool $performOnQueue;

    protected bool $keepOriginalImageFormat = false;

    protected bool $generateResponsiveImages = false;

    protected ?string $loadingAttributeValue;

    protected int $pdfPageNumber = 1;

    public function __construct(string $name)
    {
        $this->name = $name;

        $this->manipulations = (new Manipulations())
            ->optimize(config('media-library.image_optimizers'))
            ->format(Manipulations::FORMAT_JPG);

        $this->conversionFileNamer = app(config('media-library.conversion_file_namer'));

        $this->loadingAttributeValue = config('media-library.default_loading_attribute_value');

        $this->performOnQueue = config('media-library.queue_conversions_by_default', true);
    }

    public static function create(string $name)
    {
        return new static($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPerformOnCollections()
    {
        if (! count($this->performOnCollections)) {
            return ['default'];
        }

        return $this->performOnCollections;
    }

    public function extractVideoFrameAtSecond(float $timeCode): self
    {
        $this->extractVideoFrameAtSecond = $timeCode;

        return $this;
    }

    public function getExtractVideoFrameAtSecond(): float
    {
        return $this->extractVideoFrameAtSecond;
    }

    public function keepOriginalImageFormat(): self
    {
        $this->keepOriginalImageFormat = true;

        return $this;
    }

    public function shouldKeepOriginalImageFormat(): bool
    {
        return $this->keepOriginalImageFormat;
    }

    public function getManipulations(): Manipulations
    {
        return $this->manipulations;
    }

    public function removeManipulation(string $manipulationName): self
    {
        $this->manipulations->removeManipulation($manipulationName);

        return $this;
    }

    public function withoutManipulations(): self
    {
        $this->manipulations = new Manipulations();

        return $this;
    }

    public function __call($name, $arguments)
    {
        if (! method_exists($this->manipulations, $name)) {
            throw new BadMethodCallException("Manipulation `{$name}` does not exist");
        }

        $this->manipulations->$name(...$arguments);

        return $this;
    }

    public function setManipulations($manipulations): self
    {
        if ($manipulations instanceof Manipulations) {
            $this->manipulations = $this->manipulations->mergeManipulations($manipulations);
        }

        if (is_callable($manipulations)) {
            $manipulations($this->manipulations);
        }

        return $this;
    }

    public function addAsFirstManipulations(Manipulations $manipulations): self
    {
        $manipulationSequence = $manipulations->getManipulationSequence()->toArray();

        $this->manipulations
            ->getManipulationSequence()
            ->mergeArray($manipulationSequence);

        return $this;
    }

    public function performOnCollections(...$collectionNames): self
    {
        $this->performOnCollections = $collectionNames;

        return $this;
    }

    public function shouldBePerformedOn(string $collectionName): bool
    {
        //if no collections were specified, perform conversion on all collections
        if (! count($this->performOnCollections)) {
            return true;
        }

        if (in_array('*', $this->performOnCollections)) {
            return true;
        }

        return in_array($collectionName, $this->performOnCollections);
    }

    public function queued(): self
    {
        $this->performOnQueue = true;

        return $this;
    }

    public function nonQueued(): self
    {
        $this->performOnQueue = false;

        return $this;
    }

    public function nonOptimized(): self
    {
        $this->removeManipulation('optimize');

        return $this;
    }

    public function withResponsiveImages(): self
    {
        $this->generateResponsiveImages = true;

        return $this;
    }

    public function shouldGenerateResponsiveImages(): bool
    {
        return $this->generateResponsiveImages;
    }

    public function shouldBeQueued(): bool
    {
        return $this->performOnQueue;
    }

    public function getResultExtension(string $originalFileExtension = ''): string
    {
        if ($this->shouldKeepOriginalImageFormat()) {
            if (in_array($originalFileExtension, ['jpg', 'jpeg', 'pjpg', 'png', 'gif'])) {
                return $originalFileExtension;
            }
        }

        if ($manipulationArgument = $this->manipulations->getManipulationArgument('format')) {
            return $manipulationArgument;
        }

        return $originalFileExtension;
    }

    public function getConversionFile(Media $media): string
    {
        $fileName = $this->conversionFileNamer->getFileName($this, $media);
        $extension = $this->conversionFileNamer->getExtension($this, $media);

        return "{$fileName}.{$extension}";
    }

    public function useLoadingAttributeValue(string $value): self
    {
        $this->loadingAttributeValue = $value;

        return $this;
    }

    public function getLoadingAttributeValue(): ?string
    {
        return $this->loadingAttributeValue;
    }

    public function pdfPageNumber(int $pageNumber): self
    {
        $this->pdfPageNumber = $pageNumber;

        return $this;
    }

    public function getPdfPageNumber(): int
    {
        return $this->pdfPageNumber;
    }
}
