<?php

namespace App\Form\DataTransformer;

use App\Entity\Sender;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class SenderToNumberTransformer implements DataTransformerInterface
{
    /** @var ObjectManager $om */
    private $om;

    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * Transforms an integer (number) to an object (Sender)
     *
     * @param ?int $value
     * @return Sender|null
     * @throws TransformationFailedException if object (Sender) is not found.
     */
    public function reverseTransform($value): ?Sender
    {
        if (!$value) return null;

        $sender = $this->om->getRepository(Sender::class)->find($value);

        if (null === $sender) {
            throw new TransformationFailedException(sprintf(
                'Aucun expéditeur pour la valeur "%s".',
                $value
            ));
        }

        return $sender;
    }

    /**
     * Transforms an object (Sender) to an integer (number)
     * @param ?Sender $sender
     * @return int|null
     */
    public function transform($sender): ?int
    {
        if (null === $sender) return null;

        return $sender->getId();
    }
}