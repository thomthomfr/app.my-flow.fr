<?php

namespace App\Form\DataTransformer;

use App\Enum\Notification;
use Symfony\Component\Form\DataTransformerInterface;

class NotificationToEnumTransformer implements DataTransformerInterface
{

    public function transform($notificationAsArray): array
    {
        $enums = [];
        foreach ($notificationAsArray as $notification){
            $enums[] = Notification::tryFrom($notification);
        }
        return $enums;
    }


    public function reverseTransform($notificationAsInt)
    {
        return $notificationAsInt;
    }
}
