<?php

namespace App\Form\DataTransformer;

use App\Enum\NotificationType;
use Symfony\Component\Form\DataTransformerInterface;

class NotificationTypeToEnumTransformer implements DataTransformerInterface
{

    public function transform($notificationAsArray): array
    {
        $enums = [];
        foreach ($notificationAsArray as $notification){
            $enums[] = NotificationType::tryFrom($notification);
        }
        return $enums;
    }


    public function reverseTransform($notificationAsInt)
    {
        return $notificationAsInt;
    }
}
