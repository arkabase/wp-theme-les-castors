jQuery($ => {
    $('.castors-transfer-wrap #transfer').transfer({
        ...transferListItems.settings,
        callable: selected => {
            $(`.castors-location-wrap #${transferListItems.field}`).val(selected)
        },
    })
})
