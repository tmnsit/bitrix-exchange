<?php
namespace Prioritet\Exchange;



class Core{
    public function run(){
        try {
            $serialize = new Serialize();
            $products = $serialize->serialized('products');
            $import = new Import();
            $import->execute($products);
        }catch (\Error $exception){
            print_r($exception->getMessage());
        }catch (\Exception $exception){
            print_r($exception->getMessage());
        }
    }

    public function import_cards(){
        $serialize = new Serialize();
        try {
            $cards = $serialize->serialized('cards');
            $import = new Import();
            $import->execute_cards($cards);
        }catch (\Exception $exception){
           print_r($exception->getMessage());
        }

    }


}