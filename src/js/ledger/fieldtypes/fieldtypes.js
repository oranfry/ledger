(function() {
    window.fieldtypes = {
        create: function (spec) {
            let _spec = structuredClone(spec);
            delete _spec.type;

            if (!window.fieldtypes.types[spec.type]) {
                console.error('Unrecognized type: ' + spec.type);
                return;
            }

            return new window.fieldtypes.types[spec.type](_spec);
        },
        types: {}
    };
})();