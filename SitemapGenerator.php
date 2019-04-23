
<?php

namespace App\Controller;

use App\Entity\SomeClass;
use DOMDocument;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class SomeController extends AbstractController
{
    /**
     * @Route("/sitemap_generate", name="create_sitemap")
     * @param Request $request
     * @return Response
     */
    public function sitemapCreator(Request $request)
    {
        // array with all urls
        $urls = array();
        // your host name
        $hostname = $request->getSchemeAndHttpHost();

        // get data to sitemap
        $data_to_sitemap = $this->getDoctrine()->getRepository(SomeClass::class)->getDataToSitemapCreate();
        // get website languages
        $languages = json_decode(file_get_contents('data/language.json'), true);
        // get sitemap mod date
        $last_mod = date("Y-m-d");

        // main sitemap declare and encoding
        $mainSitemapData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $mainSitemapData .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xhtml=\"http://www.w3.org/1999/xhtml\" xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">";

        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = FALSE;

        // add static urls for all locales
        foreach ($languages as $key => $lang) {
            $urls[$key][] = array('loc' => $this->generateUrl('some_static_page_name', ['_locale' => $key]));
            $urls[$key][] = array('loc' => $this->generateUrl('some_static_page_name_2', ['_locale' => $key]));
            $urls[$key][] = array('loc' => $this->generateUrl('some_static_page_name_3', ['_locale' => $key]));
            $urls[$key][] = array('loc' => $this->generateUrl('some_static_page_name_4', ['_locale' => $key]));
            foreach ($data_to_sitemap as $data) {
                $urls[$key][] = array('loc' => $this->generateUrl('single_product', ['_locale' => $key, 'manufacturer' => $data['manufacturer_link'], 'model' => $data['product_link']]));
            }
        }

        // number of sitemap links
        $i = 0;

        // loop trough all data to create sitemaps
        // key is the lang name for example: pl, de, en
        // val is the array with current lang links
        foreach ($urls as $key => $val) {
            $xmlData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
            $xmlData .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xhtml=\"http://www.w3.org/1999/xhtml\" xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">";

            // number of sitemap
            $x = 1;
            foreach ($val as $value) {
                // maximum amount of links in single subsitemap
                if ($i < 50000) {
                    $xmlData .= " <url>";
                    $xmlData .= " <loc>" . $hostname . $value['loc'] . "</loc>";
                    if ($last_mod != '') {
                        $xmlData .= "  <lastmod>" . $last_mod . "</lastmod>";
                    }

//                if (array_key_exists('changefreq', $key)) {
//                    $xmlData .= "  <changefreq>" . $hostname . $key['loc'] . "</changefreq>";
//                }
//                if (array_key_exists('priority', $key)) {
//                    $xmlData .= "  <priority>" . $hostname . $key['loc'] . "</priority>";
//                }
//                if (array_key_exists('image', $key)) {
//                    $xmlData .= "<image:image>";
//                    $xmlData .= "  <priority>" . $hostname . $key['loc'] . "</priority>";
//                }
                    $xmlData .= "</url>";
                } else {
                    $xmlData .= "</urlset>";

                    $dom->loadXML($xmlData);

                    //Save XML as a file
                    $dom->save('C:\path\to\project\sitemap_' . $key . '_part_' . $x . '.xml');
                    $i = 0;
                    $x++;
                    $xmlData = null;
                    $xmlData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
                    $xmlData .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xhtml=\"http://www.w3.org/1999/xhtml\" xmlns:image=\"http://www.google.com/schemas/sitemap-image/1.1\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">";
                }
                $i++;
            }

            $xmlData .= "</urlset>";

            // Save subsitemap
            $dom->loadXML($xmlData);
            //Save XML as a file
            $dom->save('../sitemap_' . $key . '_part_' . $x . '.xml');
            $i = 0;

            // Add sitemap data that connect subsitemaps
            $mainSitemapData .= "<sitemap>";
            $mainSitemapData .= " <loc>" . $hostname . '/' . $key . '_' . $x . "</loc>";
            $mainSitemapData .= "  <lastmod>" . $last_mod . "</lastmod>";
            $mainSitemapData .= "</sitemap>";

        }

        $mainSitemapData .= "</urlset>";

        // Save main sitemap
        $dom->loadXML($mainSitemapData);
        //Save XML as a file
        $dom->save('../sitemap.xml');

        return new Response('success');

    }
}