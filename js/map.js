jQuery($ => {
    const latLongRegexp = /^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?):[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/

    const { center, zoom } = castorsMapApiSettings

    const map = L.map('castors-map', {
        center,
        zoom,
        layers: [
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }),
        ],
    })

    const markers = L.markerClusterGroup({ showCoverageOnHover: false })
    map.addLayer(markers)

    const fetchLayer = async layer => {
        const { root, nonce } = castorsMapApiSettings
        const response = await fetch(`${root}castors/v1/map/layer/${layer}`, {
            headers: { 'X-WP-Nonce': nonce },
        })
        return response.json()
    }

    const onEachFeature = (feature, layer) => {
        console.log(feature, layer)
    }

    const loadLayers = async layers => {
        markers.clearLayers()
        layers.forEach(async layer => {
            const data = await fetchLayer(layer)
            if (data?.features?.length > 0) {
                markers.addLayer(L.geoJSON(data, { onEachFeature }))
            }
        })
    }

    const setCenter = async center => {
        if (center.match(latLongRegexp)) {
            // Coordinates > recenter map
            map.panTo(center.split('-'))
        } else {
            // Username > fetch user coordinates
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
        loadLayers(layers.length > 0 ? layers : ['adherents', 'pros', 'chantiers'])
    }

    onhashchange = onHash

    onHash()
})
