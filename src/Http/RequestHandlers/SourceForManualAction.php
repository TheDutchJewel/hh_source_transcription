<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Http\RequestHandlers;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Http\RequestHandlers\AbstractTomSelectHandler;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\SearchService;
use Fisharebest\Webtrees\Source;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Validator;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function array_filter;
use function explode;
use function response;
use function view;

final class SourceForManualAction extends AbstractTomSelectHandler
{
    public function __construct(
        private readonly SearchService $search_service
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = Validator::attributes($request)->tree();

        if (!Auth::isEditor($tree)) {
            return response('');
        }

        return parent::handle($request);
    }

    /**
     * @return Collection<int,array{text:string,value:string}>
     */
    protected function search(Tree $tree, string $query, int $offset, int $limit, string $at): Collection
    {
        $access_level = Auth::accessLevel($tree);
        $source = Registry::sourceFactory()->make($query, $tree);

        if ($source instanceof Source) {
            $results = $source->canShow($access_level) ? new Collection([$source]) : new Collection();
        } else {
            $search = array_filter(explode(' ', $query));
            $results = $this->search_service
                ->searchSourcesByName([$tree], $search, $offset, $limit)
                ->filter(static fn (Source $source): bool => $source->canShow($access_level));
        }

        return $results->map(static fn (Source $source): array => [
            'text'  => view('selects/source', ['source' => $source]),
            'value' => $at . $source->xref() . $at,
        ]);
    }
}
