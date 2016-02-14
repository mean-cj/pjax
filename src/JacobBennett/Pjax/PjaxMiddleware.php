<?php

namespace JacobBennett\Pjax;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\DomCrawler\Crawler;

class PjaxMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /** @var $response Response */
        $response = $next($request);

        // Only handle non-redirections and must be a pjax-request
        if (!$response->isRedirection() && $request->pjax()) {
            $crawler = new Crawler($response->getContent());

            // Filter to title (in order to update the browser title bar)
            $response_title = $crawler->filter('head > title');


            // Filter to given container
            $response_container = $crawler->filter($request->header('X-PJAX-CONTAINER'));

            // Container must exist
            if ($response_container->count() != 0) {
                $title = '';
				$script = '';
                // If a title-attribute exists
                if ($response_title->count() != 0) {
                    $title = '<title>' . $response_title->html() . '</title>';
                }

				$html_scripts = '';

				preg_match_all("/(<(?:link|style|script)(?:.*?)(?:\/)?>)(?:(?<=^|>)[^><]+?(?=<|$))(<\/(?:style|script)>)?/i",
					$response->getContent(), $matches, PREG_PATTERN_ORDER);
				if( ! empty($matches['0']) )
				{
					$html_scripts = implode(' ',$matches['0']);
				}

				/*$crawler->filter('script')->each(function (Crawler $node, $i) use ( $html_scripts ){
					if( $html = $node->html() )
					{
						$html_scripts .= "<script type=\"text/javascript\">{$html}</script>";
					}
					else
					{
						 $html_scripts .= "<script src=\"".$node->attr('src')."\"></script>" ;
					}
				});*/


                // Set new content for the response
                $response->setContent($title . $response_container->html().$html_scripts);
            }

            // Updating address bar with the last URL in case there were redirects
            $response->header('X-PJAX-URL', $request->getRequestUri());
        }

        return $response;
    }
}
