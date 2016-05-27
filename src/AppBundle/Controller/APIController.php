<?php
/**
 * APIController
 *
 * Handles routes to Muser's API endpoints.
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Service\InstagramInterface;
use AppBundle\Service\InspirationBoard;

class APIController extends Controller
{
    /**
     * @Route("/like/{media_id}", name="like")
     */
    public function likeAction(Request $request, $media_id) {
        $insta = $insta = $this->get('instagram_interface');
        $response = $insta->like($media_id);

        return new JsonResponse($response);
    }

    /**
     * @Route("/unlike/{media_id}", name="unlike")
     */
    public function unlikeAction(Request $request, $media_id) {
        $insta = $insta = $this->get('instagram_interface');
        $response = $insta->unlike($media_id);

        return new JsonResponse($response);
    }

    /**
     * @Route("/inspire/{media_id}", name="inspire")
     */
    public function inspireAction(Request $request, $media_id) {
        $board = $this->get('inspiration_board');
        $result = $board->add($media_id);

        return new JsonResponse(['action' => 'add', 'result' => $result]);
    }

    /**
     * @Route("/uninspire/{media_id}", name="uninspire")
     */
    public function uninspireAction(Request $request, $media_id) {
        $board = $this->get('inspiration_board');
        $result = $board->remove($media_id);

        return new JsonResponse(['action' => 'remove', 'result' => $result]);
    }
}
