<?php

namespace App\Entity;

use App\Repository\AttachmentRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=AttachmentRepository::class)
 * @Vich\Uploadable
 */
class Attachment
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Assert\File(
     *     maxSize = "10M",
     *     mimeTypes = {
     *          "image/jpeg",
     *          "image/png",
     *          "image/webp",
     *          "application/pdf",
     *          "application/x-pdf",
     *          "application/msword",
     *          "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
     *          "application/vnd.ms-excel",
     *          "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *          "text/plain",
     *          "application/vnd.ms-powerpoint",
     *          "application/vnd.openxmlformats-officedocument.presentationml.presentation"
     *     },
     *     mimeTypesMessage = "Formats autorisés: jpg, png, webp, pdf, doc, docx, xls, xlsx, txt, ppt, pptx"
     *     )
     * @Vich\UploadableField(
     *     mapping = "attachment",
     *     fileNameProperty = "fileName",
     *     size = "fileSize",
     *     mimeType="fileMimeType",
     *     originalName="fileOriginalName"
     * )
     * @var File|null
     */
    private $file;

    public function getIcon(): string
    {
        $icon = 'far fa-file';

        switch ($this->fileMimeType):

            case 'application/pdf': // pdf
            case 'application/x-pdf': // pdf
                $icon = 'far fa-file-pdf';
                break;

            case 'application/msword': // doc
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document': // docx
                $icon = 'far fa-file-word';
                break;

            case 'application/vnd.ms-excel': // xls
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': // xlsx
                $icon = 'far fa-file-excel';
                break;

            case 'application/vnd.ms-powerpoint': // ppt
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation': // pptx
                $icon = 'far fa-file-powerpoint';
                break;

            case 'image/jpeg': // jpg, jpeg, jpe
            case 'image/png': // png
            case 'image/webp': // webp
                $icon = 'far fa-file-image';
                break;

            case 'text/plain' : // txt
                $icon = 'far fa-file-alt';
                break;

        endswitch;

        return $icon;
    }

    /**
     * @ORM\Column(type="string")
     * @var string|null
     */
    private $fileName;

    /**
     * @ORM\Column(type="string")
     * @var string|null
     */
    private $fileOriginalName;

    /**
     * @ORM\Column(type="string")
     * @var string|null
     */
    private $fileMimeType;

    /**
     * @ORM\Column(type="integer")
     * @var int|null
     */
    private $fileSize;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTimeInterface|null
     */
    private $updatedAt;


    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param File|UploadedFile|null $file
     * @return self
     */
    public function setFile(File $file = null)
    {
        $this->file = $file;

        if (null !== $file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): self
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getFileOriginalName(): ?string
    {
        return $this->fileOriginalName;
    }

    public function setFileOriginalName(?string $fileOriginalName): self
    {
        $this->fileOriginalName = $fileOriginalName;

        return $this;
    }

    public function getFileMimeType(): ?string
    {
        return $this->fileMimeType;
    }

    public function setFileMimeType(?string $fileMimeType): self
    {
        $this->fileMimeType = $fileMimeType;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }
}