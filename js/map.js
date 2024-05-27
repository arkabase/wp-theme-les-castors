jQuery($ => {
    const latLongRegexp = /^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?):[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/

    const { center, zoom } = castorsMapApiSettings

    const osmLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a target="_blank" href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    })

    const ignLayer = L.tileLayer(
        'https://wxs.ign.fr/choisirgeoportail/geoportail/wmts?REQUEST=GetTile&SERVICE=WMTS&VERSION=1.0.0&STYLE=normal&TILEMATRIXSET=PM&FORMAT=image/png&LAYER=GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}',
        {
            attribution: '&copy; <a target="_blank" href="https://www.geoportail.gouv.fr/">Geoportail France</a>',
        }
    )

    const aerialLayer = L.tileLayer(
        'https://wxs.ign.fr/choisirgeoportail/geoportail/wmts?REQUEST=GetTile&SERVICE=WMTS&VERSION=1.0.0&STYLE=normal&TILEMATRIXSET=PM&FORMAT=image/jpeg&LAYER=ORTHOIMAGERY.ORTHOPHOTOS&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}',
        {
            attribution: '&copy; <a target="_blank" href="https://www.geoportail.gouv.fr/">Geoportail France</a>',
        }
    )

    const map = L.map('castors-map', {
        center,
        zoom,
        minZoom: 6,
        maxZoom: 16,
        layers: [osmLayer],
    })

    L.control
        .layers({
            OpenStreetMap: osmLayer,
            'Carte IGN': ignLayer,
            'Vue aérienne': aerialLayer,
        })
        .addTo(map)

    const markers = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 60,
        spiderfyDistanceMultiplier: 1.2,
    })
    map.addLayer(markers)

    const fetchLayer = async layer => {
        const { root, nonce } = castorsMapApiSettings
        const response = await fetch(`${root}castors/v1/map/layer/${layer}`, {
            headers: { 'X-WP-Nonce': nonce },
        })
        return response.json()
    }

    const onEachFeature = (feature, layer) => {
        switch (feature.properties.type) {
            case 'member':
                layer.bindPopup(
                    `
                    <div>
                        <h3>${feature.properties.fullname}</h3>
                        <p>${feature.properties.location}</p>
                        <p><a href="/forum/user/${feature.properties.username}">Voir le profil</a></p>
                    </div>
                `,
                    { offset: [0, -5] }
                )
        }
    }

    const layers = {
        member: {
            layer: 'adherents',
            label: 'Adhérents',
            icon: {
                html: `
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:35px;height:35px" viewBox="0 0 24 24">
                        <path fill="#7d9d0d" stroke="#566C0A" d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" />
                    </svg>
                    `,
                iconSize: [40, 40],
                iconAnchor: [20, 20],
            },
            initialized: false,
            geojson: null,
        },
        expert: {
            layer: 'pros',
            label: 'Professionnels',
            icon: {
                html: `
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:35px;height:35px" viewBox="0 0 24 24">
                        <path fill="#f29f22" stroke="#A56D19" d="M12,15C7.58,15 4,16.79 4,19V21H20V19C20,16.79 16.42,15 12,15M8,9A4,4 0 0,0 12,13A4,4 0 0,0 16,9M11.5,2C11.2,2 11,2.21 11,2.5V5.5H10V3C10,3 7.75,3.86 7.75,6.75C7.75,6.75 7,6.89 7,8H17C16.95,6.89 16.25,6.75 16.25,6.75C16.25,3.86 14,3 14,3V5.5H13V2.5C13,2.21 12.81,2 12.5,2H11.5Z" />
                    </svg>
                    `,
                iconSize: [40, 40],
                iconAnchor: [20, 20],
            },
            initialized: false,
            geojson: null,
        },
        worksite: {
            layer: 'chantiers',
            type: 'worksite',
            label: 'Chantiers participatifs',
            icon: {
                html: `
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:35px;height:35px" viewBox="0 0 576 512">
                        <!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path fill="#769ACB" stroke="#516683" stroke-width="20" d="M208 64a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zM9.8 214.8c5.1-12.2 19.1-18 31.4-12.9L60.7 210l22.9-38.1C99.9 144.6 129.3 128 161 128c51.4 0 97 32.9 113.3 81.7l34.6 103.7 79.3 33.1 34.2-45.6c6.4-8.5 16.6-13.3 27.2-12.8s20.3 6.4 25.8 15.5l96 160c5.9 9.9 6.1 22.2 .4 32.2s-16.3 16.2-27.8 16.2H288c-11.1 0-21.4-5.7-27.2-15.2s-6.4-21.2-1.4-31.1l16-32c5.4-10.8 16.5-17.7 28.6-17.7h32l22.5-30L22.8 246.2c-12.2-5.1-18-19.1-12.9-31.4zm82.8 91.8l112 48c11.8 5 19.4 16.6 19.4 29.4v96c0 17.7-14.3 32-32 32s-32-14.3-32-32V405.1l-60.6-26-37 111c-5.6 16.8-23.7 25.8-40.5 20.2S-3.9 486.6 1.6 469.9l48-144 11-33 32 13.7z" />
                    </svg>
                    `,
                iconSize: [40, 40],
                iconAnchor: [20, 20],
            },
            initialized: false,
            geojson: null,
        },
        meeting: {
            layer: 'rencontres',
            type: 'meeting',
            label: 'Rencontres',
            icon: {
                html: `
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:35px;height:35px" viewBox="0 0 640 512">
                        <!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.-->
                        <path fill="#CB4335" stroke="#9C3429" stroke-width="20" d="M72 88a56 56 0 1 1 112 0A56 56 0 1 1 72 88zM64 245.7C54 256.9 48 271.8 48 288s6 31.1 16 42.3V245.7zm144.4-49.3C178.7 222.7 160 261.2 160 304c0 34.3 12 65.8 32 90.5V416c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V389.2C26.2 371.2 0 332.7 0 288c0-61.9 50.1-112 112-112h32c24 0 46.2 7.5 64.4 20.3zM448 416V394.5c20-24.7 32-56.2 32-90.5c0-42.8-18.7-81.3-48.4-107.7C449.8 183.5 472 176 496 176h32c61.9 0 112 50.1 112 112c0 44.7-26.2 83.2-64 101.2V416c0 17.7-14.3 32-32 32H480c-17.7 0-32-14.3-32-32zm8-328a56 56 0 1 1 112 0A56 56 0 1 1 456 88zM576 245.7v84.7c10-11.3 16-26.1 16-42.3s-6-31.1-16-42.3zM320 32a64 64 0 1 1 0 128 64 64 0 1 1 0-128zM240 304c0 16.2 6 31 16 42.3V261.7c-10 11.3-16 26.1-16 42.3zm144-42.3v84.7c10-11.3 16-26.1 16-42.3s-6-31.1-16-42.3zM448 304c0 44.7-26.2 83.2-64 101.2V448c0 17.7-14.3 32-32 32H288c-17.7 0-32-14.3-32-32V405.2c-37.8-18-64-56.5-64-101.2c0-61.9 50.1-112 112-112h32c61.9 0 112 50.1 112 112z" />
                    </svg>
                    `,
                iconSize: [40, 40],
                iconAnchor: [20, 20],
            },
            initialized: false,
            geojson: null,
        },
    }

    const pointToLayer = (feature, coord) => {
        return L.marker(coord, {
            icon: L.divIcon({ ...layers[feature.properties.type].icon, zIndexOffset: 10000 }),
        })
    }

    const updateMarkers = _ => {
        markers.clearLayers()
        $('#castors-map-layers > .castors-map-layer').each((i, layer) => {
            layer = $(layer)
            const config = layers[layer.data('icon')]
            const layercheck = layer.children('.castors-map-layer-check')
            config.initialized &&
                config.geojson &&
                layercheck.hasClass('checked') &&
                !markers.hasLayer(config.geojson) &&
                markers.addLayer(config.geojson)
        })
    }

    const loadLayer = async layer => {
        const data = await fetchLayer(layer)
        if (data?.features?.length > 0) {
            const geojson = L.geoJSON(data, { onEachFeature, pointToLayer })
            layers[data.features[0].properties.type].geojson = geojson
        }
    }

    const initLayers = selected => {
        loading = []
        $('#castors-map-layers > .castors-map-layer').each((i, layer) => {
            layer = $(layer)
            const config = layers[layer.data('icon')]
            if (config) {
                const layercheck = layer.children('.castors-map-layer-check')
                if (selected.includes(layer.data('layer'))) {
                    layercheck.addClass('checked')
                } else {
                    layercheck.removeClass('checked')
                }
                if (!config.initialized) {
                    config.initialized = true
                    layer.children('.castors-map-layer-icon').html(config.icon.html)
                    layer.children('.castors-map-layer-name').text(config.label)
                    layer.on('click', e => {
                        e.stopPropagation()
                        layercheck.toggleClass('checked')
                        updateMarkers()
                    })
                    loading.push(loadLayer(layer.data('layer')))
                }
            } else {
                $(el).hide()
            }
        })
        Promise.allSettled(loading).then(updateMarkers)
    }

    const setCenter = async center => {
        if (center.match(latLongRegexp)) {
            // Coordinates > recenter map
            map.panTo(center.split(':'))
        } else {
            // Username > fetch user coordinates to recenter map
            const response = await fetchLayer(`u:${center}`)
            console.log(response)
        }
    }

    const onHash = _ => {
        const layers = window.location.hash
            .substring(1)
            .split(',')
            .filter(e => !!e)
        const index = layers.findIndex(e => e.substring(0, 2) === 'c:')
        if (index >= 0) {
            const center = layers[index].substring(2)
            layers.splice(index, 1)
            setCenter(center)
        }
        initLayers(layers.length > 0 ? layers : ['adherents', 'pros', 'chantiers', 'rencontres'])
    }

    onhashchange = onHash
    onHash()
})
