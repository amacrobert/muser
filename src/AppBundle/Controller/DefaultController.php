<?php
/**
 * DefaultController
 *
 * Controller for basic user navigation.
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use AppBundle\Service\InstagramInterface;
use AppBundle\Service\InspirationBoard;
use AppBundle\Service\Authentication;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request) {

        $auth = $this->get('authentication');
        $user_data = $auth->auth($request);

        // User is unauthenticated. Serve the login page.
        if (!$user_data) {
            return $this->renderUnauthenticated($auth->getMessage());
        }

        // get posts to display
        $insta = $this->get('instagram_interface');
        $posts = $insta->getRecentTaggedMedia('canon');

        // add inspiration board data to posts
        $board = $this->get('inspiration_board');
        foreach ($posts->data as &$post) {
            $post->user_has_added = $board->isAdded($post->id) ? true : false;
        }

        return $this->render('default/home.html.twig', [
            'user' => $user_data->user,
            'media' => $posts->data,
        ]);
    }

    /**
     * @Route("/inspiration", name="inspiration")
     */
    public function inspirationAction(Request $request) {
        $auth = $this->get('authentication');
        $user_data = $auth->auth($request);

        if (!$user_data) {
            return $this->renderUnauthenticated();
        }

        $board = $this->get('inspiration_board');
        $media = $board->getImage();

        return $this->render('default/board.html.twig', [
            'user' => $user_data->user,
        ]);
    }

    /**
     * @Route("/inspiration/image", name="image");
     */
    public function inspirationBoardAction(Request $request) {
        $auth = $this->get('authentication');
        $user_data = $auth->auth($request);

        if (!$user_data) {
            $this->renderUnauthenticated();
        }

        $headers = [
            'Content-type' => 'image/jpeg',
            'Pragma' => 'no-cache',
            'Cache-Controle' => 'no-cache'
        ];
        $image = $this->get('inspiration_board')->getImage();

        // Grab the output buffer of created image
        ob_start();
        imagejpeg($image, null, 90);
        $imageStr = ob_get_clean();

        return new Response($imageStr, 200, $headers);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logoutAction(Request $request) {
        $auth = $this->get('authentication');
        $auth->logout();

        return $this->renderUnauthenticated('Logged out');
    }

    /**
     * @Route("/privacy", name="privacy")
     */
    public function privacyAction(Request $request) {
        return $this->render('default/privacy.html.twig');
    }

    /**
     * User is not authenticated
     */
    private function renderUnauthenticated($message = '') {
        $instagram = $this->getParameter('instagram');
        return $this->render('default/index.html.twig', [
            'redirect_uri' => $instagram['redirect_uri'],
            'message' => $message,
        ]);
    }
}
