<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ItemRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SignController extends AbstractController
{
    #[Route(path: '/signatures', name: 'app_sign_index', methods: ['GET'])]
    #[Route(path: '/user/{username}/signatures', name: 'app_shared_sign_index', methods: ['GET'])]
    public function index(ItemRepository $itemRepository): Response
    {
        $this->denyAccessUnlessFeaturesEnabled(['signs']);

        $signs = [];
        $items = $itemRepository->findWithSigns();
        foreach ($items as $item) {
            foreach ($item->getData() as $sign) {
                $signs[] = $sign;
            }
        }

        return $this->render('App/Sign/index.html.twig', [
            'signs' => $signs,
        ]);
    }
}
