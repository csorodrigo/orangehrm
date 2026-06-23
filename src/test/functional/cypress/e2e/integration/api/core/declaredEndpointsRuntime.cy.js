/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

const SAFE_PARAMETER_VALUES = {
  date: '2026-01-01',
  endDate: '2026-01-01',
  fromDate: '2026-01-01',
  toDate: '2026-01-01',
  imageName: 'clientLogo',
  langCode: 'pt_BR',
  language: 'pt_BR',
  languageId: '4',
  module: 'core',
  provider: 'local',
  screen: 'endpoint-validation',
  userName: 'endpoint-validation',
};

const SAFE_QUERY_VALUES = {
  apiv2_admin_validate_user_name: {
    userName: 'endpoint-validation',
  },
};

const resolvePath = (path, parameters) => {
  return parameters.reduce((resolvedPath, parameter) => {
    const value = SAFE_PARAMETER_VALUES[parameter] ?? '0';
    return resolvedPath.replace(`{${parameter}}`, value);
  }, path);
};

const appendQueryString = (url, query) => {
  if (!query) {
    return url;
  }

  return `${url}?${new URLSearchParams(query).toString()}`;
};

const methodValidations = (route) => {
  if (route.methods.length === 0) {
    return [];
  }

  const validations = Array.isArray(route.methodValidation)
    ? route.methodValidation
    : [route.methodValidation];

  return route.methods.map((method) => ({
    method,
    validation: validations.find((entry) => entry.method === method),
  }));
};

describe('Core - Declared Endpoints Runtime', function () {
  before(function () {
    cy.fixture('user').then(({admin}) => {
      cy.apiLogin(admin);
    });
  });

  it('all declared endpoint methods respond without server errors', function () {
    cy.fixture('endpoint-validation/endpoint-validation-matrix').then(
      (data) => {
        const requests = data.matrix.flatMap((route) =>
          methodValidations(route).map(({method, validation}) => ({
            method,
            url: appendQueryString(
              resolvePath(route.path, route.parameters),
              SAFE_QUERY_VALUES[route.route],
            ),
            body: validation?.payload ?? undefined,
            route: route.route,
          })),
        );

        expect(requests).to.have.length(data.methodCount);

        requests.forEach((request) => {
          cy.request({
            method: request.method,
            url: request.url,
            body: request.body,
            failOnStatusCode: false,
            followRedirect: false,
          }).then((response) => {
            expect(
              response.status,
              `${request.method} ${request.route} ${request.url}`,
            ).to.be.lessThan(500);
          });
        });
      },
    );
  });
});
