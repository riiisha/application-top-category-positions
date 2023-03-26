<?php

namespace App\Controller;

use App\Repository\PositionsRepository;
use App\Service\PositionCacheService;
use DateTime;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;


class ApiTopCategoryPositionController extends AbstractController
{

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    #[Route('/api/appTopCategory', name: 'app_api_top_category')]
    public function topCategoryByDate(
        PositionsRepository  $positionsRepository,
        Request              $request,
        PositionCacheService $positionCacheService
    ): Response
    {
        try {
            $date = new DateTime($request->query->get('date'));
        } catch (Exception) {
            $data = [
                'status_code' => 400,
                'message' => 'Bad Request',
                'data' => 'Bad Request'
            ];
            return $this->json($data, 400, [], ['json_encode_options' => JSON_UNESCAPED_UNICODE]);
        }
        if (($dateStr = $date->format('Y-m-d')) == (new DateTime())->format('Y-m-d')) {
            $positions = $positionsRepository->getPositionByDate($dateStr);
        } else {
            $positions = $positionCacheService->getCachedPositionByDate($dateStr);
        }

        if (empty($positions)) {
            $data = [
                'status_code' => 404,
                'message' => 'Not Found',
                'data' => 'Not Found'
            ];
            return $this->json($data, 404, [], ['json_encode_options' => JSON_UNESCAPED_UNICODE]);
        } else {
            $positionByCategory = [];
            foreach ($positions as $position) {
                $positionByCategory[$position['category']] = $position['position'];
            }
            $data = [
                'status_code' => 200,
                'message' => 'ok',
                'data' => $positionByCategory
            ];
            return $this->json($data, 200, [], ['json_encode_options' => JSON_UNESCAPED_UNICODE]);
        }
    }
}