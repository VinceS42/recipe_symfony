<?php

namespace App\Services;

use App\Entity\Recipe;
use App\Entity\Ingredient;
use Doctrine\ORM\EntityManagerInterface;

class RecipeImportService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function importFromJson(string $filePath): void
    {
        $jsonData = file_get_contents($filePath);
        $data = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON file: ' . json_last_error_msg());
        }

        if (!isset($data['recipes'])) {
            throw new \Exception('Invalid JSON structure: missing "recipes" key');
        }

        // Step 1: Create a set of unique ingredients
        $uniqueIngredients = [];

        foreach ($data['recipes'] as $recipeData) {
            foreach ($recipeData['ingredients'] as $ingredientName) {
                $cleanedIngredientName = trim(strtolower($ingredientName));
                $uniqueIngredients[$cleanedIngredientName] = $cleanedIngredientName;
            }
        }

        // Step 2: Insert unique ingredients into the database if they don't exist
        foreach ($uniqueIngredients as $ingredientName) {
            $ingredient = $this->entityManager->getRepository(Ingredient::class)
                ->findOneBy(['nameIngredient' => $ingredientName]);

            if (!$ingredient) {
                $ingredient = new Ingredient();
                $ingredient->setNameIngredient($ingredientName);
                $this->entityManager->persist($ingredient);
            }
        }

        // Flush to ensure all unique ingredients are in the database
        $this->entityManager->flush();

        // Step 3: Process each recipe and associate ingredients
        foreach ($data['recipes'] as $recipeData) {
            $existingRecipe = $this->entityManager->getRepository(Recipe::class)
                ->findOneBy(['recipeName' => $recipeData['name']]);

            if (!$existingRecipe) {
                $recipe = new Recipe();
                $recipe->setRecipeName($recipeData['name']);
                $recipe->setPreparationTime($recipeData['preparationTime']);
                $recipe->setCookingTime($recipeData['cookingTime']);
                $recipe->setNumberOfPeople($recipeData['serves']);

                foreach ($recipeData['ingredients'] as $ingredientName) {
                    $cleanedIngredientName = trim(strtolower($ingredientName));
                    $ingredient = $this->entityManager->getRepository(Ingredient::class)
                        ->findOneBy(['nameIngredient' => $cleanedIngredientName]);

                    if ($ingredient) {
                        $recipe->addIngredient($ingredient);
                    }
                }

                $this->entityManager->persist($recipe);
            } else {
                foreach ($recipeData['ingredients'] as $ingredientName) {
                    $cleanedIngredientName = trim(strtolower($ingredientName));
                    $ingredient = $this->entityManager->getRepository(Ingredient::class)
                        ->findOneBy(['nameIngredient' => $cleanedIngredientName]);

                    if ($ingredient && !$existingRecipe->getIngredient()->contains($ingredient)) {
                        $existingRecipe->addIngredient($ingredient);
                    }
                }

                $this->entityManager->persist($existingRecipe);
            }
        }

        $this->entityManager->flush();
    }
}
