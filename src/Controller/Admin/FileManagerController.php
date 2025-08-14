<?php

namespace App\Controller\Admin;

use App\Service\FileManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/dashboard/admin/filemanager")
 * @IsGranted("ROLE_USER")
 */
class FileManagerController extends AbstractController
{
    private FileManagerService $fileManagerService;

    public function __construct(FileManagerService $fileManagerService)
    {
        $this->fileManagerService = $fileManagerService;
    }

    /**
     * @Route("/browse/{type}/{id}", name="filemanager_browse", methods={"GET"})
     */
    public function browse(string $type, int $id): Response
    {
        $folder = $this->fileManagerService->getFolderPath($type, $id);
        $files = $this->fileManagerService->getFiles($folder);
        
        return $this->render('admin/filemanager/modal.html.twig', [
            'files' => $files,
            'type' => $type,
            'id' => $id,
            'uploadUrl' => $this->generateUrl('filemanager_upload', ['type' => $type, 'id' => $id]),
        ]);
    }

    /**
     * @Route("/upload/{type}/{id}", name="filemanager_upload", methods={"POST"})
     */
    public function upload(Request $request, string $type, int $id): JsonResponse
    {
        $files = $request->files->get('files');
        
        $uploadedFiles = [];
        $errors = [];


        if (!$files) {
            return new JsonResponse([
                'error' => 'Aucun fichier fourni ou types de fichiers non autorisés. Formats acceptés : jpg, png, webp, pdf, doc, docx, xls, xlsx, txt, ppt, pptx'
            ], 400);
        }

        // Ensure files is an array
        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            try {
                $result = $this->fileManagerService->uploadFile($file, $type, $id);
                $uploadedFiles[] = $result;
            } catch (\Exception $e) {
                $errors[] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage()
                ];
            }
        }

        if (!empty($errors) && empty($uploadedFiles)) {
            return new JsonResponse(['errors' => $errors], 400);
        }

        return new JsonResponse([
            'success' => true,
            'files' => $uploadedFiles,
            'errors' => $errors
        ]);
    }

    /**
     * @Route("/delete/{type}/{id}", name="filemanager_delete", methods={"DELETE"})
     */
    public function delete(Request $request, string $type, int $id): JsonResponse
    {
        $filename = $request->request->get('filename');
        
        if (!$filename) {
            return new JsonResponse(['error' => 'Nom de fichier manquant'], 400);
        }

        try {
            $this->fileManagerService->deleteFile($filename, $type, $id);
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @Route("/list/{type}/{id}", name="filemanager_list", methods={"GET"})
     */
    public function list(string $type, int $id): JsonResponse
    {
        try {
            $folder = $this->fileManagerService->getFolderPath($type, $id);
            $files = $this->fileManagerService->getFiles($folder);
            
            return new JsonResponse([
                'success' => true,
                'files' => $files
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}