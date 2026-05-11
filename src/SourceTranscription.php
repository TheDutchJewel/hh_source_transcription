<?php

/*
 * webtrees: online genealogy application
 * Copyright (C) 2026 webtrees development team
 *                    <https://webtrees.net>
 *
 * Source Transcription (webtrees custom module):
 * Copyright (C) 2026 Hermann Hartenthaler
 *                     <https://ahnen.hartenthaler.eu>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; If not, see <https://www.gnu.org/licenses/>.
 *
 * Source Transcription
 * A webtrees (https://webtrees.net) 2.2 custom module to transcribe sources
 */

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\SourceTranscription;

use Aura\Router\Exception\ImmutableProperty;
use Aura\Router\Exception\RouteAlreadyExists;
use Fisharebest\{Localization\Translation,
    Webtrees\DB,
    Webtrees\FlashMessages,
    Webtrees\I18N,
    Webtrees\Auth,
    Webtrees\Module\AbstractModule,
    Webtrees\Module\ModuleConfigInterface,
    Webtrees\Module\ModuleConfigTrait,
    Webtrees\Module\ModuleCustomInterface,
    Webtrees\Module\ModuleCustomTrait,
    Webtrees\Registry,
    Webtrees\Validator,
    Webtrees\View,
    Webtrees\Webtrees,
    Webtrees\Menu,
    Webtrees\Tree,
    Webtrees\Services\ModuleService,
    Webtrees\Services\TreeService,
    Webtrees\Services\UserService,
    Webtrees\Module\ModuleMenuInterface,
    Webtrees\Module\ModuleMenuTrait};
use Psr\{Container\ContainerExceptionInterface,
    Container\NotFoundExceptionInterface,
    Http\Message\ResponseInterface,
    Http\Message\ServerRequestInterface};
use Schwendinger\Webtrees\Module\LinkEnhancer\Services\MarkdownEditorActivationService;
use Hartenthaler\{Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Repository\SettingsRepository,
    Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Schema\SchemaManager,
    Webtrees\Module\SourceTranscription\Domain\ValueObject\NoteStrategy,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\CollaborationStatusAction,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\CompareRevisionsAction,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\CreateManualAction,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\DashboardAction,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\DetailAction,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\ManualStatusAction,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\MakeRevisionCurrentAction,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\OpenCollaborationAction,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\SaveNoteAsRevisionAction,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\StoreManualAction,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\SourceForManualAction,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\UpdateCurrentNoteAction,
    Webtrees\Module\SourceTranscription\Http\RequestHandlers\MediaForSourceAction,
    Webtrees\Module\SourceTranscription\Infrastructure\WhatsNew\WhatsNewInterface};

final class SourceTranscription extends AbstractModule implements
    ModuleCustomInterface, ModuleConfigInterface, ModuleMenuInterface
{
    use ModuleCustomTrait;
    use ModuleConfigTrait;
    use ModuleMenuTrait;

    //Custom module version
	public const string CUSTOM_VERSION = '2.2.5.0';

    //Supported webtrees version
    public const string MINIMUM_WEBTREES_VERSION = '2.2.5';

    //Repository of the custom module
    public const string REPOSITORY = 'https://github.com/';

    // User at GitHub
    public const string CUSTOM_GITHUB_USER = 'hartenthaler';

    //Title of custom module
    public const string CUSTOM_TITLE = 'hh_source_transcription';

    //GitHub repository
    public const string GITHUB_REPO = self::CUSTOM_GITHUB_USER . "/" . self::CUSTOM_TITLE;

    // URL to the latest version of the custom module
    public const string CUSTOM_LAST = self::REPOSITORY . self::GITHUB_REPO . '/blob/main/latest-version.txt';

	//Author of the custom module
	public const string CUSTOM_AUTHOR = 'Hermann Hartenthaler';

    //Used database schema version
    public const int CURRENT_SCHEMA_VERSION = 3;

    //Default tag values for transcriptions (NOTE <tag_prefix><tag_value>)
    public const string DEFAULT_TAG_PREFIX = 'TAG: ';
    public const string DEFAULT_TAG_VALUE = 'Transcription';
    public const string DEFAULT_TAG = self::DEFAULT_TAG_PREFIX . self::DEFAULT_TAG_VALUE;


    //Options
    public const string DEFAULT_NOTE_STRATEGY = 'default_note_strategy';
    public const string DEFAULT_TAG_TEXT = 'default_tag_text';
    public const string TINY_MDE = 'tiny_mde';
    public const string TAGGING_SUPPORT = 'tagging_support';
    public const string WHATS_NEW = 'whats_new';

    //ROUTE
    private const string ROUTE_GET_NAME_DASHBOARD = 'source-transcription-dashboard';
    private const string ROUTE_PATH_DASHBOARD = '/tree/{tree}/source-transcriptions';
    private const string ROUTE_GET_NAME_CREATE_MANUAL = 'source-transcription-create-manual';
    private const string ROUTE_POST_NAME_STORE_MANUAL = 'source-transcription-store-manual';
    private const string ROUTE_PATH_CREATE_MANUAL = '/tree/{tree}/source-transcriptions/create-manual';
    private const string ROUTE_POST_NAME_UPDATE_NOTE = 'source-transcription-update-note';
    private const string ROUTE_PATH_UPDATE_NOTE = '/tree/{tree}/source-transcriptions/{transcription_id}/update-note';
    private const string ROUTE_POST_NAME_SAVE_NOTE_AS_REVISION = 'source-transcription-save-note-as-revision';
    private const string ROUTE_PATH_SAVE_NOTE_AS_REVISION = '/tree/{tree}/source-transcriptions/{transcription_id}/save-note-as-revision';
    private const string ROUTE_POST_NAME_MAKE_REVISION_CURRENT = 'source-transcription-make-revision-current';
    private const string ROUTE_PATH_MAKE_REVISION_CURRENT = '/tree/{tree}/source-transcriptions/{transcription_id}/revisions/{revision_id}/make-current';
    private const string ROUTE_POST_NAME_OPEN_COLLABORATION = 'source-transcription-open-collaboration';
    private const string ROUTE_PATH_OPEN_COLLABORATION = '/tree/{tree}/source-transcriptions/{transcription_id}/open-collaboration';
    private const string ROUTE_POST_NAME_COLLABORATION_STATUS = 'source-transcription-collaboration-status';
    private const string ROUTE_PATH_COLLABORATION_STATUS = '/tree/{tree}/source-transcriptions/{transcription_id}/collaboration-status';
    private const string ROUTE_POST_NAME_MANUAL_STATUS = 'source-transcription-manual-status';
    private const string ROUTE_PATH_MANUAL_STATUS = '/tree/{tree}/source-transcriptions/{transcription_id}/manual-status';
    //private const string ROUTE_GET_NAME_MEDIA_FOR_SOURCE = 'source-transcription-media-for-source';
    private const string ROUTE_PATH_MEDIA_FOR_SOURCE = '/tree/{tree}/source-transcriptions/media-for-source';
    private const string ROUTE_PATH_SOURCE_FOR_MANUAL = '/tree/{tree}/source-transcriptions/source-for-manual';
    private const string ROUTE_GET_NAME_DETAIL = 'source-transcription-detail';
    private const string ROUTE_PATH_DETAIL = '/tree/{tree}/source-transcriptions/{transcription_id}';
    private const string ROUTE_GET_NAME_COMPARE_REVISIONS = 'source-transcription-compare-revisions';
    private const string ROUTE_PATH_COMPARE_REVISIONS = '/tree/{tree}/source-transcriptions/{transcription_id}/compare-revisions';

    /**
     * SourceTranscription constructor.
     */
    public function __construct(
        //private readonly SettingsRepository $settingsRepository
    )
    {
    }

    /**
     * Initialization.
     *
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ImmutableProperty
     * @throws RouteAlreadyExists
     */
    public function boot(): void
    {
        $schema_manager = Registry::container()->get(SchemaManager::class);
        //check database schema version and update if necessary
        if (!$schema_manager->allTablesExist()) {
            $schema_manager->deleteAllTables();
            $schema_manager->updateSchema('\Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Schema',
                'schema_version', self::CURRENT_SCHEMA_VERSION);
            $this->setInitialDefaults();
        } else {
            $schema_manager->updateSchema('\Hartenthaler\Webtrees\Module\SourceTranscription\Infrastructure\Persistence\Schema',
                'schema_version', self::CURRENT_SCHEMA_VERSION);
        }

        //Register a namespace for the views.
        View::registerNamespace('hh_source_transcription', $this->resourcesFolder() . 'views/');

        //Enable the TinyMDE editor of custom module linkenhancer
        $settingsRepository = Registry::container()->get(SettingsRepository::class);
        $this->toggleTinyMde($settingsRepository->get(self::TINY_MDE) == 'enabled');
        //if ($settingsRepository->get(self::TINY_MDE) == 'enabled') {
        //   $this->enableTinyMde();
        //}

        //Register routes
        $router = Registry::routeFactory()->routeMap();

        $router->get(
            self::ROUTE_GET_NAME_DASHBOARD,
            self::ROUTE_PATH_DASHBOARD,
            DashboardAction::class
        );

        $router->get(
            self::ROUTE_GET_NAME_CREATE_MANUAL,
            self::ROUTE_PATH_CREATE_MANUAL,
            CreateManualAction::class
        );

        $router->post(
            self::ROUTE_POST_NAME_STORE_MANUAL,
            self::ROUTE_PATH_CREATE_MANUAL,
            StoreManualAction::class
        );

        $router->post(
            self::ROUTE_POST_NAME_UPDATE_NOTE,
            self::ROUTE_PATH_UPDATE_NOTE,
            UpdateCurrentNoteAction::class
        );

        $router->post(
            self::ROUTE_POST_NAME_SAVE_NOTE_AS_REVISION,
            self::ROUTE_PATH_SAVE_NOTE_AS_REVISION,
            SaveNoteAsRevisionAction::class
        );

        $router->post(
            self::ROUTE_POST_NAME_MAKE_REVISION_CURRENT,
            self::ROUTE_PATH_MAKE_REVISION_CURRENT,
            MakeRevisionCurrentAction::class
        );

        $router->post(
            self::ROUTE_POST_NAME_OPEN_COLLABORATION,
            self::ROUTE_PATH_OPEN_COLLABORATION,
            OpenCollaborationAction::class
        );

        $router->post(
            self::ROUTE_POST_NAME_COLLABORATION_STATUS,
            self::ROUTE_PATH_COLLABORATION_STATUS,
            CollaborationStatusAction::class
        );

        $router->post(
            self::ROUTE_POST_NAME_MANUAL_STATUS,
            self::ROUTE_PATH_MANUAL_STATUS,
            ManualStatusAction::class
        );

        $router->get(
            SourceForManualAction::class,
            self::ROUTE_PATH_SOURCE_FOR_MANUAL,
            SourceForManualAction::class
        );

        $router->get(
            MediaForSourceAction::class,
            self::ROUTE_PATH_MEDIA_FOR_SOURCE,
            MediaForSourceAction::class
        );

        $router->get(
            self::ROUTE_GET_NAME_COMPARE_REVISIONS,
            self::ROUTE_PATH_COMPARE_REVISIONS,
            CompareRevisionsAction::class
        );

        //Generic Route at the end!
        $router->get(
            self::ROUTE_GET_NAME_DETAIL,
            self::ROUTE_PATH_DETAIL,
            DetailAction::class
        );

        $this->flashWhatsNew();
    }

    /**
     * Display "What's new" messages as flash messages.
     *
     * @return void
     */
    private function flashWhatsNew(): void
    {
        if (!Auth::check()) {
            return;
        }

        $namespace = 'Hartenthaler\\Webtrees\\Module\\SourceTranscription\\Infrastructure\\WhatsNew';
        $pref = self::WHATS_NEW;
        $current_version = (int) $this->getPreference($pref, '0');

        $target_version = $current_version;
        while (class_exists($namespace . '\\WhatsNew' . $target_version)) {
            $target_version++;
        }

        while ($current_version < $target_version) {
            $class = $namespace . '\\WhatsNew' . $current_version;

            if (class_exists($class)) {
                /** @var WhatsNewInterface $whatsNew */
                $whatsNew = new $class();
                FlashMessages::addMessage(I18N::translate("What's new?") . " " . $whatsNew->getMessage());
            }
            $current_version++;

            $this->setPreference($pref, (string) $current_version);
        }
    }

    /**
     * set the initial defaults for the settings
     *
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function setInitialDefaults(): void
    {
        $settingsRepository = Registry::container()->get(SettingsRepository::class);
        $settingsRepository->setSchemaVersion(0);
        $settingsRepository->set(self::DEFAULT_NOTE_STRATEGY, 'update_if_unchanged');
        $settingsRepository->set(self::DEFAULT_TAG_TEXT, self::DEFAULT_TAG);
        $settingsRepository->set(self::TINY_MDE, 'enabled');
        $settingsRepository->set(self::TAGGING_SUPPORT, 'enabled');
    }


    /**
     * toggles registration for using markdown editor on note fields provided by linkenhancer custom module
     * registration is persisted by linkenhancer custom module so it's not necessary to force registering again each time
     * @param bool $enable
     * @param bool $force
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function toggleTinyMde(bool $enable, bool $force = false): void
    {
        $class = "Schwendinger\\Webtrees\\Module\\LinkEnhancer\\Services\\MarkdownEditorActivationService";
        if (Registry::container()->has($class)) {
            /** @var MarkdownEditorActivationService $mde_service */
            $mde_service = Registry::container()->get($class);
            $existingRule = $mde_service->getCustomRule(self::CUSTOM_TITLE);
            if (
                $force ||
                $enable && !$existingRule ||
                !$enable && $existingRule
            ) {
                $mde_service->setCustomRule(
                    self::CUSTOM_TITLE,  // module name as key
                    $enable ? ["source-transcription-detail", "source-transcription-create-manual"] : [], // handler: usually the short class name / last part of the route name - see js console with enabled debug info
                    $enable ? ["textarea[id$='_text']"] : [] // filter: querySelector filter expressions; here: textarea id ends with "_text"
                );
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\AbstractModule::resourcesFolder()
     */
    public function resourcesFolder(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR;
    }

    /**
     * Get the namespace for the views
     *
     * @return string
     */
    public static function viewsNamespace(): string
    {
        return self::activeModuleName();
    }

    public static function activeModuleName(): string
    {
        return basename(dirname(__DIR__, 1));
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\AbstractModule::title()
     */
    public function title(): string
    {
        return /* I18N: Name of a module/tab on the individual page. */ I18N::translate("Source Transcription");
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\AbstractModule::description()
     */
    public function description(): string
    {
        return I18N::translate('Manage source transcriptions with manual and provider-based workflows.');
    }

    /**
     * Minimum webtrees version
     *
     * @return string
     */
    public function minimumVersion(): string
    {
        return self::MINIMUM_WEBTREES_VERSION;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleAuthorName()
     */
    public function customModuleAuthorName(): string
    {
        return self::CUSTOM_AUTHOR;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleVersion()
     */
    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    /**
     * A URL that will provide the latest version of this module.
     *
     * @return string
     */
    public function customModuleLatestVersionUrl(): string
    {
        return self::CUSTOM_LAST;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customModuleSupportUrl()
     */
    public function customModuleSupportUrl(): string
    {
        return self::REPOSITORY . self::GITHUB_REPO;
    }

    /**
     * define the default menu order (can be overwritten by admin)
     *
     * @return int
     */
    public function defaultMenuOrder(): int
    {
        return 80;
    }

    /**
     * @param Tree $tree
     *
     * @return bool
     */
    public function canAccess(Tree $tree): bool
    {
        return Auth::accessLevel($tree) >= Auth::ACCESS_MEMBER;
    }

    /**
     * set the main menu entry
     *
     * @param Tree $tree
     * @return Menu|null
     */
    public function getMenu(Tree $tree): ?Menu
    {
        if (!Auth::isMember($tree)) {
            return null;
        }

        return new Menu(
            $this->menuIcon() . I18N::translate('Transcriptions'),
            e(route('source-transcription-dashboard', [
                'tree' => $tree->name(),
            ])),
            $this->name() . ' menu-source-transcription'
        );
    }

    /**
     * Inline SVG icon for the main menu entry.
     *
     * @return string
     */
    private function menuIcon(): string
    {
        $icon_file = $this->resourcesFolder() . 'images' . DIRECTORY_SEPARATOR . 'source-transcription.svg';

        if (!is_file($icon_file)) {
            return '';
        }

        $icon = file_get_contents($icon_file);

        if ($icon === false) {
            return '';
        }

        return '<span aria-hidden="true" style="display:block;width:6.4em;height:3.2em;margin:0 auto .25rem;line-height:1">' . $icon . '</span>';
    }

    /**
     * {@inheritDoc}
     *
     * @param string $language
     *
     * @return array
     *
     * @see \Fisharebest\Webtrees\Module\ModuleCustomInterface::customTranslations()
     */
    public function customTranslations(string $language): array
    {
        $lang_dir   = $this->resourcesFolder() . 'lang' . DIRECTORY_SEPARATOR;
        $file       = $lang_dir . $language . '.mo';
        if (file_exists($file)) {
            return (new Translation($file))->asArray();
        } else {
            return [];
        }
    }

    /**
     * Whether the module runs with the webtrees version of this installation
     *
     * @return bool
     */
    public static function runsWithInstalledWebtreesVersion(): bool
    {
        if (version_compare(Webtrees::VERSION, self::MINIMUM_WEBTREES_VERSION, '>=')) {
            return true;
        }

        return false;
    }

    /**
     * View module settings in the control panel
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        $settings = Registry::container()->get(SettingsRepository::class);

        $tag_text = $settings->get('default_tag_text', self::DEFAULT_TAG_PREFIX . self::DEFAULT_TAG_VALUE);
        $tag_value = $this->tagValueFromTagText($tag_text);

        return $this->adminSettingsResponse($settings, $tag_value);
    }

    private function adminSettingsResponse(
        SettingsRepository $settings,
        string $tag_value,
        ?array $consistency_check = null
    ): ResponseInterface {
        return $this->viewResponse(self::CUSTOM_TITLE . '::' . 'admin-settings', [
            'title'                         => $this->title(),
            'module'                        => $this,
            'runs_with_webtrees_version'    => SourceTranscription::runsWithInstalledWebtreesVersion(),
            'diagnostics'                   => $this->adminDiagnostics($settings),
            'consistency_check'             => $consistency_check,
            'default_note_strategy'         => $settings->get(
                'default_note_strategy',
                NoteStrategy::default()
            ),
            'note_strategies'               => NoteStrategy::labels(),
            'tiny_mde'                      => $settings->get('tiny_mde', ''),
            'tagging_support'               => $settings->get('tagging_support', 'enabled'),
            'tag_prefix'                    => self::DEFAULT_TAG_PREFIX,
            'tag_value'                     => $tag_value,
        ]);
    }

    /**
     * Save module settings after returning from the control panel
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function postAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $save = Validator::parsedBody($request)->string('save', '');
        $run_consistency_check = Validator::parsedBody($request)->string('run_consistency_check', '');

        if ($run_consistency_check === '1') {
            $this->layout = 'layouts/administration';

            $settings = Registry::container()->get(SettingsRepository::class);
            $tag_text = $settings->get('default_tag_text', self::DEFAULT_TAG_PREFIX . self::DEFAULT_TAG_VALUE);
            $tag_value = $this->tagValueFromTagText($tag_text);

            return $this->adminSettingsResponse($settings, $tag_value, $this->runConsistencyCheck($settings));
        }

        //Save the received settings to the user preferences
        if ($save === '1') {
            // tbd: use Validator::parsedBody($request)->string|boolean|...('xxx', ''|true|...);
            $params = (array)$request->getParsedBody();

            $tag_value = $this->normalizeTagValue(trim((string)($params['tag_value'] ?? self::DEFAULT_TAG_VALUE)));
            $note_strategy = (string)($params['default_note_strategy']);

            //Set default NOTE strategy if not set or incorrect set
            if (!NoteStrategy::isValid($note_strategy)) {
                $note_strategy = NoteStrategy::default();
            }

            $settings = Registry::container()->get(SettingsRepository::class);
            $settings->set('default_tag_text', self::DEFAULT_TAG_PREFIX . $tag_value);
            $settings->set('default_note_strategy', $note_strategy);
            $settings->set('tiny_mde', (string) ($params['tiny_mde'] ?? ''));
            $settings->set('tagging_support', (string) ($params['tagging_support'] ?? ''));

            //Finally, show a success message
            FlashMessages::addMessage(
                I18N::translate('The preferences for the module “%s” have been updated.', $this->title()),
                'success'
            );
        }
        return redirect($this->getConfigLink());
    }

    /**
     * @return array{errors:array<int,array{tree:string, transcription:string, message:string}>, warnings:array<int,array{tree:string, transcription:string, message:string}>}
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function runConsistencyCheck(SettingsRepository $settings): array
    {
        $schema_manager = new SchemaManager();

        $result = [
            'errors'   => [],
            'warnings' => [],
        ];

        if (!$schema_manager->allTablesExist()) {
            $this->addConsistencyIssue(
                $result,
                'errors',
                '',
                '',
                I18N::translate('The module database tables are incomplete.')
            );

            return $result;
        }

        $trees = [];
        foreach (Registry::container()->get(TreeService::class)->all() as $tree) {
            $trees[$tree->id()] = $tree;
        }

        $tagging_enabled = $settings->get(self::TAGGING_SUPPORT, 'enabled') === 'enabled';

        $transcriptions = DB::table(SchemaManager::TABLE_TRANSCRIPTIONS)
            ->where('is_active', '=', true)
            ->orderBy('tree_id')
            ->orderBy('id')
            ->get();

        foreach ($transcriptions as $transcription) {
            $tree = $trees[(int) $transcription->tree_id] ?? null;
            $tree_label = $tree === null ? I18N::translate('Unknown tree') . ' #' . (int) $transcription->tree_id : $tree->title();
            $transcription_label = '#' . (int) $transcription->id . ' ' . (string) $transcription->title;

            if ($tree === null) {
                $this->addConsistencyIssue($result, 'errors', $tree_label, $transcription_label, I18N::translate('The referenced family tree no longer exists.'));
                continue;
            }

            $source = Registry::sourceFactory()->make((string) $transcription->source_xref, $tree);
            $media = $transcription->media_xref === null ? null : Registry::mediaFactory()->make((string) $transcription->media_xref, $tree);
            $target = $transcription->media_xref === null ? $source : $media;

            if ($source === null) {
                $this->addConsistencyIssue($result, 'errors', $tree_label, $transcription_label, I18N::translate('The referenced source does not exist: %s', (string) $transcription->source_xref));
            }

            if ($transcription->media_xref !== null && $media === null) {
                $this->addConsistencyIssue($result, 'errors', $tree_label, $transcription_label, I18N::translate('The referenced media object does not exist: %s', (string) $transcription->media_xref));
            }

            if ($source !== null && $transcription->media_xref !== null && !$this->gedcomLinksToNoteOrMedia($source->gedcom(), 'OBJE', (string) $transcription->media_xref)) {
                $this->addConsistencyIssue($result, 'warnings', $tree_label, $transcription_label, I18N::translate('The referenced media object is no longer linked to the source.'));
            }

            if ($transcription->current_note_xref === null || trim((string) $transcription->current_note_xref) === '') {
                $this->addConsistencyIssue($result, 'errors', $tree_label, $transcription_label, I18N::translate('The transcription has no current NOTE.'));
            } else {
                $current_note = Registry::noteFactory()->make((string) $transcription->current_note_xref, $tree);

                if ($current_note === null) {
                    $this->addConsistencyIssue($result, 'errors', $tree_label, $transcription_label, I18N::translate('The current NOTE does not exist: %s', (string) $transcription->current_note_xref));
                } elseif ($target === null || !$this->gedcomLinksToNoteOrMedia($target->gedcom(), 'NOTE', (string) $transcription->current_note_xref)) {
                    $this->addConsistencyIssue($result, 'errors', $tree_label, $transcription_label, I18N::translate('The current NOTE is not linked to the expected source or media object: %s', (string) $transcription->current_note_xref));
                }
            }

            if ($tagging_enabled) {
                if ($transcription->tag_note_xref === null || trim((string) $transcription->tag_note_xref) === '') {
                    $this->addConsistencyIssue($result, 'warnings', $tree_label, $transcription_label, I18N::translate('Tagging is enabled, but the transcription has no tag NOTE.'));
                } else {
                    $tag_note = Registry::noteFactory()->make((string) $transcription->tag_note_xref, $tree);

                    if ($tag_note === null) {
                        $this->addConsistencyIssue($result, 'warnings', $tree_label, $transcription_label, I18N::translate('The tag NOTE does not exist: %s', (string) $transcription->tag_note_xref));
                    } elseif ($target === null || !$this->gedcomLinksToNoteOrMedia($target->gedcom(), 'NOTE', (string) $transcription->tag_note_xref)) {
                        $this->addConsistencyIssue($result, 'warnings', $tree_label, $transcription_label, I18N::translate('The tag NOTE is not linked to the expected source or media object: %s', (string) $transcription->tag_note_xref));
                    }
                }
            }
        }

        $revision_rows = DB::table(SchemaManager::TABLE_REVISIONS . ' AS r')
            ->leftJoin(SchemaManager::TABLE_TRANSCRIPTIONS . ' AS t', 't.id', '=', 'r.transcription_id')
            ->select(['r.*', 't.tree_id', 't.title'])
            ->orderBy('r.transcription_id')
            ->orderBy('r.revision_no')
            ->get();

        $current_revision_counts = [];

        foreach ($revision_rows as $revision) {
            $transcription_id = (int) $revision->transcription_id;
            $current_revision_counts[$transcription_id] ??= 0;

            if ((bool) $revision->is_current_revision) {
                $current_revision_counts[$transcription_id]++;
            }

            if ($revision->tree_id === null) {
                $this->addConsistencyIssue($result, 'errors', '', '#' . $transcription_id, I18N::translate('Revision %s belongs to a transcription that no longer exists.', (string) $revision->revision_no));
                continue;
            }

            if ($revision->generated_note_xref !== null && trim((string) $revision->generated_note_xref) !== '') {
                $tree = $trees[(int) $revision->tree_id] ?? null;
                if ($tree === null) {
                    continue;
                }

                $note = Registry::noteFactory()->make((string) $revision->generated_note_xref, $tree);
                if ($note === null) {
                    $this->addConsistencyIssue(
                        $result,
                        'errors',
                        $tree->title(),
                        '#' . $transcription_id . ' ' . (string) $revision->title,
                        I18N::translate('Revision %s references a NOTE that no longer exists: %s', (string) $revision->revision_no, (string) $revision->generated_note_xref)
                    );
                }
            }
        }

        foreach ($transcriptions as $transcription) {
            $transcription_id = (int) $transcription->id;
            $tree = $trees[(int) $transcription->tree_id] ?? null;
            $tree_label = $tree === null ? I18N::translate('Unknown tree') . ' #' . (int) $transcription->tree_id : $tree->title();
            $transcription_label = '#' . $transcription_id . ' ' . (string) $transcription->title;
            $revision_count = DB::table(SchemaManager::TABLE_REVISIONS)->where('transcription_id', '=', $transcription_id)->count();
            $current_count = $current_revision_counts[$transcription_id] ?? 0;

            if ($revision_count === 0) {
                $this->addConsistencyIssue($result, 'errors', $tree_label, $transcription_label, I18N::translate('The transcription has no revisions.'));
            } elseif ($current_count !== 1) {
                $this->addConsistencyIssue($result, 'errors', $tree_label, $transcription_label, I18N::translate('The transcription has %s current revisions; expected exactly one.', (string) $current_count));
            }

            $current_revision_note_xref = DB::table(SchemaManager::TABLE_REVISIONS)
                ->where('transcription_id', '=', $transcription_id)
                ->where('is_current_revision', '=', true)
                ->value('generated_note_xref');

            if (
                $current_revision_note_xref !== null &&
                $transcription->current_note_xref !== null &&
                (string) $current_revision_note_xref !== (string) $transcription->current_note_xref
            ) {
                $this->addConsistencyIssue($result, 'warnings', $tree_label, $transcription_label, I18N::translate('The current revision references a different NOTE than the transcription current NOTE.'));
            }
        }

        $user_service = Registry::container()->get(UserService::class);
        $collaborators = DB::table(SchemaManager::TABLE_COLLABORATORS . ' AS c')
            ->leftJoin(SchemaManager::TABLE_TRANSCRIPTIONS . ' AS t', 't.id', '=', 'c.transcription_id')
            ->select(['c.*', 't.tree_id', 't.title'])
            ->where('c.is_active', '=', true)
            ->orderBy('c.transcription_id')
            ->get();

        foreach ($collaborators as $collaborator) {
            if ($user_service->find((int) $collaborator->user_id) !== null) {
                continue;
            }

            $tree = $collaborator->tree_id === null ? null : ($trees[(int) $collaborator->tree_id] ?? null);
            $this->addConsistencyIssue(
                $result,
                'warnings',
                $tree?->title() ?? '',
                '#' . (int) $collaborator->transcription_id . ' ' . (string) ($collaborator->title ?? ''),
                I18N::translate('An active collaboration entry references a user that no longer exists: %s', (string) $collaborator->user_id)
            );
        }

        return $result;
    }

    private function addConsistencyIssue(array &$result, string $severity, string $tree, string $transcription, string $message): void
    {
        $result[$severity][] = [
            'tree'          => $tree,
            'transcription' => $transcription,
            'message'       => $message,
        ];
    }

    private function gedcomLinksToNoteOrMedia(string $gedcom, string $tag, string $xref): bool
    {
        return preg_match('/^\d+\s+' . preg_quote($tag, '/') . '\s+@' . preg_quote($xref, '/') . '@/mu', $gedcom) === 1;
    }

    /**
     * @return array{
     *     schema: array{current:int, target:int, tables_exist:bool},
     *     tiny_mde: array{setting_enabled:bool, service_available:bool, custom_rule_registered:bool},
     *     linkenhancer: array{installed:bool, enabled:bool, name:string},
     *     transcription_trees: array<int,array{title:string, name:string, transcriptions:int, revisions:int}>,
     *     trees: array<int,array{title:string, name:string, format_text:string, markdown_enabled:bool}>
     * }
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function adminDiagnostics(SettingsRepository $settings): array
    {
        $schema_manager = new SchemaManager();
        $tables_exist = $schema_manager->allTablesExist();
        try {
            $current_schema_version = $settings->getSchemaVersion();
        } catch (\Throwable) {
            $current_schema_version = 0;
        }
        $module_service = Registry::container()->get(ModuleService::class);
        $linkenhancer = $module_service->findByName('_linkenhancer_', true);
        $service_available = Registry::container()->has(MarkdownEditorActivationService::class);
        $custom_rule_registered = false;

        if ($service_available) {
            /** @var MarkdownEditorActivationService $mde_service */
            $mde_service = Registry::container()->get(MarkdownEditorActivationService::class);
            $custom_rule_registered = $mde_service->getCustomRule(self::CUSTOM_TITLE) !== [];
        }

        return [
            'schema' => [
                'current'      => $current_schema_version,
                'target'       => self::CURRENT_SCHEMA_VERSION,
                'tables_exist' => $tables_exist,
            ],
            'tiny_mde' => [
                'setting_enabled'        => $settings->get(self::TINY_MDE, '') === 'enabled',
                'service_available'      => $service_available,
                'custom_rule_registered' => $custom_rule_registered,
            ],
            'linkenhancer' => [
                'installed' => $linkenhancer !== null,
                'enabled'   => $linkenhancer !== null && $linkenhancer->isEnabled(),
                'name'      => $linkenhancer?->title() ?? 'linkenhancer',
            ],
            'transcription_trees' => $this->transcriptionStatusByTree($tables_exist),
            'trees' => $this->markdownStatusByTree(),
        ];
    }

    /**
     * @return array<int,array{title:string, name:string, transcriptions:int, revisions:int}>
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function transcriptionStatusByTree(bool $tables_exist): array
    {
        if (!$tables_exist) {
            return [];
        }

        $trees = Registry::container()->get(TreeService::class)->all();
        $status_by_tree = [];

        foreach ($trees as $tree) {
            $status_by_tree[$tree->id()] = [
                'title'             => $tree->title(),
                'name'              => $tree->name(),
                'transcriptions'    => 0,
                'revisions'         => 0,
            ];
        }

        $transcriptions_alias = DB::prefix('t');
        $revisions_alias = DB::prefix('r');

        $rows = DB::table(SchemaManager::TABLE_TRANSCRIPTIONS . ' AS t')
            ->leftJoin(SchemaManager::TABLE_REVISIONS . ' AS r', static function ($join): void {
                $join->on('r.transcription_id', '=', 't.id');
            })
            ->select([
                't.tree_id',
                DB::raw('COUNT(DISTINCT `' . $transcriptions_alias . '`.`id`) AS transcriptions'),
                DB::raw('COUNT(`' . $revisions_alias . '`.`id`) AS revisions'),
            ])
            ->where('t.is_active', '=', true)
            ->groupBy('t.tree_id')
            ->get();

        foreach ($rows as $row) {
            $tree_id = (int) $row->tree_id;

            if (!isset($status_by_tree[$tree_id])) {
                continue;
            }

            $status_by_tree[$tree_id]['transcriptions'] = (int) $row->transcriptions;
            $status_by_tree[$tree_id]['revisions'] = (int) $row->revisions;
        }

        return array_values(array_filter($status_by_tree, static fn (array $status): bool => $status['transcriptions'] > 0));
    }

    /**
     * @return array<int,array{title:string, name:string, format_text:string, markdown_enabled:bool}>
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function markdownStatusByTree(): array
    {
        $trees = Registry::container()->get(TreeService::class)->all();
        $status_by_tree = [];

        foreach ($trees as $tree) {
            $format_text = $tree->getPreference('FORMAT_TEXT');

            $status_by_tree[] = [
                'title'             => $tree->title(),
                'name'              => $tree->name(),
                'format_text'       => $format_text,
                'markdown_enabled'  => $format_text === 'markdown',
            ];
        }

        return $status_by_tree;
    }

    /**
     * define tag value based on parameter or set to default if not defined already
     *
     * @param string $tag_value
     * @return string
     */
    private function normalizeTagValue(string $tag_value): string
    {
        $tag_value = trim($tag_value);

        if (str_starts_with(strtoupper($tag_value), 'TAG:')) {
            $tag_value = trim(substr($tag_value, 4));
        }

        return $tag_value !== '' ? $tag_value : self::DEFAULT_TAG_VALUE;
    }

    /**
     * define tag value (like "TAG: Transcription") from tag text (like "Transcription" or "TAG: Transcription"))
     *
     * @param string|null $tag_text
     * @return string
     */
    private function tagValueFromTagText(?string $tag_text): string
    {
        return $this->normalizeTagValue((string) $tag_text);
    }
}
