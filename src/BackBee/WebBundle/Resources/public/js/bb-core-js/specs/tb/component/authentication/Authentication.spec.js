define(['Core', 'component!session', 'component!authentication', 'Core/Request', 'Core/Response', 'jquery'], function (Core, session, authentication, Request, Response, jQuery) {
    'use strict';

    var request = new Request(),
        apiKey = '12345jtf6',
        apiSignature = '548zeeaT',
        fakeXhr = {
            responseText: '',
            status: '',
            getAllResponseHeaders: function () {
                return '';
            }
        };

    localStorage.clear();
    session.destroy();

    describe('Authentication test', function () {

        it('Testing onBeforeSend event', function () {
            session.onBeforeSend(request);
            expect(request.getHeader('X-API-KEY')).toBe(null);
            expect(request.getHeader('X-API-SIGNATURE')).toBe(null);

            localStorage.setItem('bb-session-auth', JSON.stringify({key: apiKey, signature: apiSignature}));
            session.load();

            session.onBeforeSend(request);
            expect(request.getHeader('X-API-KEY')).toEqual(apiKey);
            expect(request.getHeader('X-API-SIGNATURE')).toEqual(apiSignature);
        });

        it('Testing authentication function', function () {
            var username = 'foo',
                password = 'bar',
                callback = jasmine.createSpy(),
                data = {
                    username: username,
                    password: password
                };

            spyOn(jQuery, 'ajax').and.callFake(function () {
                var d = jQuery.Deferred();

                d.resolve(data, '', fakeXhr);
                expect(data).toEqual(data);

                callback();

                return d.promise();
            });

            authentication.authenticate(username, password);
            expect(callback).toHaveBeenCalled();
        });

        it('Testing persist session', function () {
            session.setKey(apiKey);
            session.setSignature(apiSignature);

            localStorage.clear();
            session.persist();

            expect(localStorage.getItem('bb-session-auth')).toEqual(JSON.stringify({key: apiKey, signature: apiSignature}));
        });

        it('Testing onRequestDone event', function () {
            var response = new Response();

            response.addHeader('X-API-KEY', apiKey);
            response.addHeader('X-API-SIGNATURE', apiSignature);

            localStorage.clear();

            authentication.onRequestDone(response);
            expect(localStorage.getItem('bb-session-auth')).toEqual(JSON.stringify({key: apiKey, signature: apiSignature}));
        });

        it('Testing onRequestFail event', function () {
            var response = new Response();

            response.setStatus(401);
            session.onRequestFail(response);
            expect(Core.get('is_connected')).toEqual(false);

            response.setStatus(403);
            session.onRequestFail(response);
        });

        it('Testing logOut function', function () {
            localStorage.setItem('bb-session-auth', JSON.stringify({key: apiKey, signature: apiSignature}));

            window.onbeforeunload = function () {
                return false;
            };

            session.destroy();
            expect(localStorage.getItem('bb-session-auth')).toEqual(null);
        });

        it('Testing onLogOut event', function () {
            localStorage.setItem('bb5=-session-auth', JSON.stringify({key: apiKey, signature: apiSignature}));

            window.onbeforeunload = function () {
                return false;
            };

            session.destroy();
            expect(localStorage.getItem('bb-session-auth')).toEqual(null);
        });
    });
});
