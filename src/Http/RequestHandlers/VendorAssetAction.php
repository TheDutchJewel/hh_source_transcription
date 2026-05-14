<?php

/*
 * webtrees: online genealogy application
 * Source Transcription (webtrees custom module)
 */

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription\Http\RequestHandlers;

use Fisharebest\Webtrees\Mime;
use Fisharebest\Webtrees\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function file_get_contents;
use function is_file;
use function pathinfo;
use function response;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strtoupper;

use const DIRECTORY_SEPARATOR;
use const PATHINFO_EXTENSION;

final class VendorAssetAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $asset = Validator::attributes($request)->string('asset');

        if (!str_starts_with($asset, 'openseadragon/') || str_contains($asset, '..')) {
            return response('', 404);
        }

        $file = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $asset);

        if (!is_file($file)) {
            return response('', 404);
        }

        $extension = strtoupper(pathinfo($file, PATHINFO_EXTENSION));

        return response((string) file_get_contents($file), 200, [
            'cache-control' => 'public,max-age=31536000',
            'content-type'  => Mime::TYPES[$extension] ?? Mime::DEFAULT_TYPE,
        ]);
    }
}
