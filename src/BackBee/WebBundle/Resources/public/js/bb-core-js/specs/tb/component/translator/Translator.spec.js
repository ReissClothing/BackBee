define(
    [
        'jquery',
        'component!translator',
        'component!logger',
        'Core/Renderer',
        'es5-shim/es5-shim'
    ],
    function (jQuery, Translator, Logger, Renderer) {

        'use strict';

        var locale_fr = "fr_FR",
            locale_en = 'en_US';

        Translator.init({'base': 'src/tb/i18n/'});

        Translator.loadCatalog(locale_fr);
        Translator.loadCatalog(locale_en);

        Renderer.addFilter('trans', jQuery.proxy(Translator.translate, Translator));
        Renderer.addFunction('trans', jQuery.proxy(Translator.translate, Translator));

        describe("Translator core library test suite", function () {

            it("Should translate the key from default catalog", function () {
                expect(Translator.translate('app_validate')).toEqual('Validate');
            });

            it("Should set a new locale", function () {
                Translator.setLocale(locale_fr);
                expect(Translator.getLocale()).toEqual(locale_fr);
            });

            it("Should translate the key with another locale than default's one", function () {
                Translator.setLocale(locale_fr);
                expect(Translator.translate('app_validate')).toEqual('Valider');
            });

            it("Should log a notice if the translation for the selected catalog is not found", function () {
                spyOn(Logger, "notice").and.returnValue('The key "foo.bar" is malformed.').and.callThrough();
                Translator.setLocale(locale_fr);
                Translator.translate('app_only_not_default');
                expect(Logger.notice).toHaveBeenCalled();
            });

            it("Should return at least the key if key is malformed", function () {
                Translator.setLocale(locale_fr);
                expect(Translator.translate('app_only_not_default')).toEqual('app_only_not_default');
            });

            it("Should be able to render key in Renderer scope (Filter)", function () {
                expect(Renderer.render("{{ 'app_validate' | trans }}")).toEqual('Valider');
            });

            it("Should be able to render key in Renderer scope (Function", function () {
                expect(Renderer.render("{{ trans('app_validate') }}")).toEqual('Valider');
            });
        });
    }
);
