<?php

namespace App\Controller;

use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyAbstractController;

abstract class AbstractController extends SymfonyAbstractController
{
    /**
     * @param string $type
     * @param null $message
     * @param null $icon
     */
    protected function addAlert(string $type, $message = null, $icon = null)
    {
        switch ($type):
            case 'success':
                $icon = $icon ?: 'fas fa-check-circle';
                $message = $message ?: 'Modifications enregistrées.';
                break;
            case 'warning':
                $icon = $icon ?: 'fas fa-exclamation-circle';
                break;
            case 'info':
                $icon = $icon ?: 'fas fa-info-circle';
                break;
            case 'danger':
            case 'error':
                $type = 'danger';
                $icon = $icon ?: 'fas fa-exclamation-triangle';
                $message = $message ?: 'Une erreur est survenue. Veuillez réessayer.';
                break;
        endswitch;

        if ($icon) {

            $message = '<i class="'.$icon.' fa-fw me-2"></i>'.$message;
        }

        parent::addFlash(
            $type,
            $message
        );
    }
}