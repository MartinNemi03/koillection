<?php

declare(strict_types=1);

namespace App\Service;

use App\Attribute\Upload;
use App\Enum\ConfigurationEnum;
use App\Repository\ConfigurationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use function PHPUnit\Framework\matches;

class ImageHandler
{
    private readonly PropertyAccessor $accessor;

    public function __construct(
        private readonly RandomStringGenerator $randomStringGenerator,
        private readonly ThumbnailGenerator $thumbnailGenerator,
        private readonly Security $security,
        private readonly DiskUsageCalculator $diskUsageCalculator,
        private readonly ConfigurationRepository $configurationRepository,
        private readonly string $publicPath,
        private readonly string $env
    ) {
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    public function upload(object $entity, string $property, Upload $attribute): void
    {
        $file = $this->accessor->getValue($entity, $property);

        if ($file instanceof UploadedFile) {
            $user = $this->security->getUser();
            if ($this->env === 'test') {
                $relativePath = 'uploads/tests/';
            } else {
                $relativePath = 'uploads/'.$user->getId().'/';
            }

            $absolutePath = $this->publicPath.'/'.$relativePath;

            $generatedName = $this->randomStringGenerator->generate(20);
            $extension = str_replace('image/', '', mime_content_type($file->getRealPath()));

            $fileName = $generatedName.'_original.'.$extension;
            $this->diskUsageCalculator->hasEnoughSpaceForUpload($user, $file);
            $file->move($absolutePath, $fileName);

            $this->removeOldFile($entity, $attribute);
            $this->accessor->setValue($entity, $attribute->getPath(), $relativePath.$fileName);

            if ($attribute->getMaxWidth() || $attribute->getMaxHeight()) {
                $this->thumbnailGenerator->crop($absolutePath.'/'.$fileName, $attribute->getMaxWidth(), $attribute->getMaxHeight());
            }

            $thumbnailFormat = $this->configurationRepository->findOneBy(['label' => ConfigurationEnum::THUMBNAILS_FORMAT])?->getValue() ?? $extension;
            if (null !== $attribute->getSmallThumbnailPath()) {
                $smallThumbnailFileName = $generatedName.'_small.'.$thumbnailFormat;
                $result = $this->thumbnailGenerator->generate($absolutePath.'/'.$fileName, $absolutePath.'/'.$smallThumbnailFileName, 300, $thumbnailFormat);
                $this->accessor->setValue($entity, $attribute->getSmallThumbnailPath(), $result ? $relativePath.$smallThumbnailFileName : null);
            }

            if (null !== $attribute->getLargeThumbnailPath()) {
                $largeThumbnailFileName = $generatedName.'_large.'.$thumbnailFormat;
                $result = $this->thumbnailGenerator->generate($absolutePath.'/'.$fileName, $absolutePath.'/'.$largeThumbnailFileName, 600, $thumbnailFormat);
                $this->accessor->setValue($entity, $attribute->getLargeThumbnailPath(), $result ? $relativePath.$largeThumbnailFileName : null);
            }

            if (null !== $attribute->getOriginalFilenamePath()) {
                $this->accessor->setValue($entity, $attribute->getOriginalFilenamePath(), $file->getClientOriginalName());
            }

            $this->accessor->setValue($entity, $property, null);
        }
    }

    public function setFileFromFilename(object $entity, string $property, Upload $attribute): void
    {
        $path = $this->accessor->getValue($entity, $attribute->getPath());

        if (null !== $path) {
            $file = new File($this->publicPath.'/'.$path, false);
            $this->accessor->setValue($entity, $property, $file);
        }
    }

    public function removeOldFile(object $entity, Upload $attribute): void
    {
        if (null !== $attribute->getPath()) {
            $path = $this->accessor->getValue($entity, $attribute->getPath());
            if (null !== $path) {
                @unlink($this->publicPath.'/'.$path);
            }
        }

        if (null !== $attribute->getSmallThumbnailPath()) {
            $path = $this->accessor->getValue($entity, $attribute->getSmallThumbnailPath());
            if (null !== $path) {
                @unlink($this->publicPath.'/'.$path);
            }
        }

        if (null !== $attribute->getLargeThumbnailPath()) {
            $path = $this->accessor->getValue($entity, $attribute->getLargeThumbnailPath());
            if (null !== $path) {
                @unlink($this->publicPath.'/'.$path);
            }
        }
    }
}
