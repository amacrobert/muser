<?php
/**
 * InspirationBoard
 *
 * Handles functionality related the Inspiration board
 */

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\Session\Session;

define("IMGSIZE", 640);

class InspirationBoard {

    private $items;
    private $insta;

    public function __construct($insta) {
        $this->insta = $insta;
    }

    /**
     * Add a post to the user's inspiration board
     *
     * @param string $id
     *   The instagram media id of the post to add
     */
    public function add($id) {
        $this->items = [];

        if (!$this->isAdded($id)) {
            $this->items[] = $id;
            $this->persist();
        }

        return $this->items;
    }

    /**
     * Remove a post from the user's inspiration board
     *
     * @param string $id
     *   The instagram media id of the post to remove
     */
    public function remove($id) {
        $index = array_search($id, $this->getItems());
        if ($index) {
            unset($this->items[$index]);
            // Rebase array keys for proper json encoding
            $this->items = array_values($this->items);
            $this->persist();
        }

        return $this->items;
    }

    /**
     * Check if a post is on the inspiration board
     */
    public function isAdded($id) {
        return in_array($id, $this->getItems());
    }

    /**
     * Get all the posts on the inspiration board.
     *
     * @return array
     *   A list of media ids
     */
    public function getItems() {
        if (empty($this->items)) {
            $session = new Session();
            $this->items = json_decode($session->get('inspiration_board', '[]'));
        }

        return $this->items;
    }

    /**
     * Save changes to the inspiration board.
     */
    private function persist() {
        $session = new Session();
        $items = $this->getItems();
        $session->set('inspiration_board', json_encode($items));
    }

    /**
     * Create the inspiration board image.
     *
     * Use a recursive divide-and-conquer algorithm to display any number of posts.
     */
    public function getImage() {
        $items = $this->getItems();
        shuffle($items); // Randomize order of items to generate different baords each load
        $board_arrangement = $this->arrange($items);

        $image = imagecreatetruecolor(IMGSIZE, IMGSIZE);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $black);

        return $this->drawImage($image, $board_arrangement, 0, 0, IMGSIZE);
    }

    private function arrange($items) {
        $n = count($items);

        if ($n <= 4) {
            return $items;
        }

        $n_top_half = round($n / 2);
        $n_bot_half = $n - $n_top_half;
        // "nq_X" means "number of pictures in quadrant X"
        $nq_1 = round($n_top_half / 2);
        $nq_2 = $n_top_half - $nq_1;
        $nq_3 = round($n_bot_half / 2);
        $nq_4 = $n_bot_half - $nq_3;

        return [
            $this->arrange(array_slice($items, 0, $nq_1)),
            $this->arrange(array_slice($items, $nq_1, $nq_2)),
            $this->arrange(array_slice($items, $nq_1 + $nq_2, $nq_3)),
            $this->arrange(array_slice($items, $nq_1 + $nq_2 + $nq_3, $nq_4)),
        ];
    }

    private function drawImage($image, $items, $x_origin, $y_origin, $size) {
        // No images added to the board
        if (empty($items)) {
            return $image;
        }

        // Leafs -- stitch together photos
        if (!is_array(current($items))) {
            $uris = [];
            foreach ($items as $item) {
                $media = $this->insta->getMedia($item);
                $source_images[] = imagecreatefromjpeg($media->data->images->standard_resolution->url);
            }

            $n = count($source_images);

            /* 1 image - cover the entire square
             *
             * + - - - +
             * |       |
             * |   A   |
             * |       |
             * + - - - +
             */
            if ($n == 1) {
                // Image 1
                imagecopyresampled(
                    $image, $source_images[0],  // destination, source images
                    $x_origin, $y_origin,       // x & y destination
                    0, 0,                       // x & y source
                    $size, $size,               // width & height destination
                    640, 640                    // width & height source
                );
            }

            /* 2 images - place vertical middle portions next to each other
             *
             * + - + - +
             * |   |   |
             * | A | B |
             * |   |   |
             * + - + - +
             */
            elseif ($n == 2) {
                // Image A
                imagecopyresampled(
                    $image, $source_images[0],  // destination, source images
                    $x_origin, $y_origin,       // x & y destination
                    160, 0,                     // x & y source
                    $size / 2, $size,           // width & height destination
                    320, 640                    // width & height source
                );

                // Image B
                imagecopyresampled(
                    $image, $source_images[1],  // destination, source images
                    $x_origin + ($size / 2), $y_origin, // x & y destination
                    160, 0,                     // x & y source
                    $size / 2, $size,           // width & height destination
                    320, 640                    // width & height source
                );
            }

            /* 3 images - place 1st vertical, 2nd two in right half stacked on top of one another
             *
             * + - + - +
             * |   | B |
             * | A + - +
             * |   | C |
             * + - + - +
             */
            elseif ($n == 3) {
                // Image A
                imagecopyresampled(
                    $image, $source_images[0],  // destination, source images
                    $x_origin, $y_origin,       // x & y destination
                    160, 0,                     // x & y source
                    $size / 2, $size,           // width & height destination
                    320, 640                    // width & height source
                );

                // Image B
                imagecopyresampled(
                    $image, $source_images[1],  // destination, source images
                    $x_origin + ($size / 2), $y_origin, // x & y destination
                    0, 0,                       // x & y source
                    $size / 2, $size / 2,       // width & height destination
                    640, 640                    // width & height source
                );

                // Image C
                imagecopyresampled(
                    $image, $source_images[2],  // destination, source images
                    $x_origin + ($size / 2), $y_origin + ($size / 2), // x & y destination
                    0, 0,                       // x & y source
                    $size / 2, $size / 2,       // width & height destination
                    640, 640                    // width & height source
                );
            }

            /* 4 images - place in a 2x2 grid
             *
             * + - + - +
             * | A | B |
             * | - + - +
             * | C | D |
             * + - + - +
             */
            elseif ($n == 4) {
                $this->drawImage($image, [$items[0]], $x_origin + $size / 2, $y_origin, $size / 2);
                $this->drawImage($image, [$items[1]], $x_origin, $y_origin, $size / 2);
                $this->drawImage($image, [$items[2]], $x_origin, $y_origin + $size / 2, $size / 2);
                $this->drawImage($image, [$items[3]], $x_origin + ($size / 2), $y_origin + ($size / 2), $size / 2);
            }
        }

        // Branches -- Divide into 4 quadrants and iterate
        else {
            $this->drawImage($image, $items[0], $x_origin + $size / 2, $y_origin, $size / 2);
            $this->drawImage($image, $items[1], $x_origin, $y_origin, $size / 2);
            $this->drawImage($image, $items[2], $x_origin, $y_origin + $size / 2, $size / 2);
            $this->drawImage($image, $items[3], $x_origin + ($size / 2), $y_origin + ($size / 2), $size / 2);
        }

        return $image;
    }
}
