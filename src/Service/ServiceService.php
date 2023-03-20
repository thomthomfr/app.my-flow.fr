<?php

namespace App\Service;


class ServiceService
{
    public function checkService($service, $company)
    {
        $priceProduct = $service->getPrice();
        if ($priceProduct != 0){
            $resaleProduct = $service->getResale() * 1 - $company->getCustomerDiscount() / 100;
            $benefice = $resaleProduct - $priceProduct;
            $currentMarge = ($benefice / $priceProduct) * 100;
        }else{
            $currentMarge = 100;
        }


        return $currentMarge;
    }
}
