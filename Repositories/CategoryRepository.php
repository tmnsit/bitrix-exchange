<?php
declare(strict_types=1);
namespace Prioritet\Exchange\Repositories;

class CategoryRepository{

    private array $categories = [];

    /**
     * @throws \Exception
     */
    public function addFromString(string $path, string $code_path): int
    {
        $pathCategoriesCode = explode('/', $code_path);
        $categoriesPaths = explode('/', $path);
        $code = 0;
        if($pathCategoriesCode[0] && $categoriesPaths[0]){
            $name = explode('*', $categoriesPaths[0])[0];
            $code = explode('*', $pathCategoriesCode[0])[0];
            $this->categories[$code] = ['name' => $name];
        }
        $code = (int) $code;

        if(!$code){
            throw new \Exception('Не валидная категория');
        }
        return $code;

    }


    public function getCategories() : array
    {
        return $this->categories;
    }
}