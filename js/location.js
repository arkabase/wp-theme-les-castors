jQuery($ => {
    $('.user-location-wrap #location').autocomplete({
        source: async function (query, done) {
            try {
                const postcode = query.term.substring(0, 5)
                const response = await fetch('https://geo.api.gouv.fr/communes?codePostal=' + postcode + '&format=geojson&geometry=mairie')
                const data = await response.json()
                if (!data?.features?.length) {
                    throw new Error("Not a valid postcode")
                }
                done(data.features.map(f => ({
                    value: postcode + ', ' + f.properties.nom,
                    postcode,
                    code: f.properties.code,
                    department: f.properties.codeDepartement,
                    coordinates: f.geometry.coordinates,
                })))
            } catch(err) {
                console.log('error', err)
                done([])
            }
        },
        search: e => {
            $('.user-location-wrap #location-details').val('')
            if (!e.target.value.match(/^[0-9]{5}/g)) {
                e.preventDefault()
            }
        },
        select: (event, ui) => {
            const selected = ui.item
            $('.user-location-wrap #location-details').val(JSON.stringify(selected))
        },
    });
});
