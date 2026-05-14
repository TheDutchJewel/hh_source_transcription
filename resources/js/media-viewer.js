(function () {
    'use strict';

    function setVisibleMedia(select) {
        document.querySelectorAll('.source-transcription-media-preview').forEach(function (element) {
            element.style.display = element.dataset.mediaFileIndex === select.value ? '' : 'none';
        });

        initVisibleImageViewers();
    }

    function initImageViewer(viewer) {
        if (viewer.dataset.viewerInitialized === '1') {
            return;
        }

        const stage = viewer.querySelector('.source-transcription-image-stage');
        const imageUrl = viewer.dataset.imageUrl;
        const imageTitle = viewer.dataset.imageTitle || '';
        const zoomIn = viewer.querySelector('[data-zoom-in]');
        const zoomOut = viewer.querySelector('[data-zoom-out]');
        const zoomReset = viewer.querySelector('[data-zoom-reset]');

        if (!stage || !imageUrl) {
            return;
        }

        if (!window.OpenSeadragon) {
            const image = document.createElement('img');
            let fallbackZoom = 1;

            image.src = imageUrl;
            image.alt = imageTitle;
            image.className = 'source-transcription-image-fallback';
            stage.appendChild(image);

            function renderFallbackZoom() {
                image.style.setProperty('--st-fallback-zoom', String(fallbackZoom));
            }

            zoomIn?.addEventListener('click', function () {
                fallbackZoom = Math.min(fallbackZoom * 1.25, 8);
                renderFallbackZoom();
            });

            zoomOut?.addEventListener('click', function () {
                fallbackZoom = Math.max(fallbackZoom * 0.8, 0.25);
                renderFallbackZoom();
            });

            zoomReset?.addEventListener('click', function () {
                fallbackZoom = 1;
                renderFallbackZoom();
            });

            renderFallbackZoom();
            viewer.dataset.viewerInitialized = '1';
            return;
        }

        const osd = window.OpenSeadragon({
            element: stage,
            tileSources: {
                type: 'image',
                url: imageUrl
            },
            showNavigationControl: false,
            drawer: 'canvas',
            gestureSettingsMouse: {
                clickToZoom: false
            },
            maxZoomPixelRatio: 4,
            visibilityRatio: 0.5,
            constrainDuringPan: true,
            preserveViewport: true
        });

        let osdReady = false;
        const pendingActions = [];

        function withOpenViewer(action) {
            if (osdReady) {
                action();
                return;
            }

            pendingActions.push(action);
        }

        function zoomByFactor(factor) {
            withOpenViewer(function () {
                const center = osd.viewport.getCenter();
                const currentZoom = osd.viewport.getZoom();

                osd.viewport.zoomTo(currentZoom * factor, center);
                osd.viewport.applyConstraints();
            });
        }

        osd.addHandler('open', function () {
            osdReady = true;

            while (pendingActions.length > 0) {
                pendingActions.shift()();
            }
        });

        viewer.dataset.viewerInitialized = '1';

        zoomIn?.addEventListener('click', function () {
            zoomByFactor(1.25);
        });

        zoomOut?.addEventListener('click', function () {
            zoomByFactor(0.8);
        });

        zoomReset?.addEventListener('click', function () {
            withOpenViewer(function () {
                osd.viewport.goHome();
            });
        });
    }

    function initVisibleImageViewers() {
        document.querySelectorAll('.source-transcription-media-preview').forEach(function (preview) {
            if (preview.style.display === 'none') {
                return;
            }

            preview.querySelectorAll('[data-source-transcription-image-viewer]').forEach(initImageViewer);
        });
    }

    function initStopButton(button) {
        button.addEventListener('click', function () {
            const container = button.closest('.source-transcription-av-viewer');
            const media = container?.querySelector('audio, video');

            if (!media) {
                return;
            }

            media.pause();

            try {
                media.currentTime = 0;
            } catch (error) {
                // Some remote streams do not allow seeking.
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('media-file-select');

        if (select) {
            select.addEventListener('change', function () {
                setVisibleMedia(select);
            });
        }

        initVisibleImageViewers();
        document.querySelectorAll('[data-media-stop]').forEach(initStopButton);
    });
}());
