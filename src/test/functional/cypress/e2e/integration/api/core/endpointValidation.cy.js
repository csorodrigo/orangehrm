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

describe('Core - Endpoint Validation Matrix', function () {
  it('covers every declared route with method validation metadata', function () {
    cy.fixture('endpoint-validation/endpoint-validation-matrix').then(
      (data) => {
        expect(data.routeCount).to.equal(543);
        expect(data.matrix).to.have.length(data.routeCount);

        const matrixMethodCount = data.matrix.reduce(
          (count, route) => count + route.methods.length,
          0,
        );
        expect(matrixMethodCount).to.equal(data.methodCount);

        data.matrix.forEach((route) => {
          expect(route.route).to.be.a('string').and.not.be.empty;
          expect(route.path).to.be.a('string').and.not.be.empty;
          expect(route.source).to.match(/routes\.yaml$/);
          expect(route.methods).to.be.an('array');
          if (route.methods.length === 0) {
            expect(route.methodValidation).to.deep.equal({});
            return;
          }

          const methodValidation = Array.isArray(route.methodValidation)
            ? route.methodValidation
            : [route.methodValidation];
          expect(methodValidation).to.have.length(route.methods.length);

          route.methods.forEach((method) => {
            expect(method).to.match(/^[A-Z]+$/);
            const validation = methodValidation.find(
              (entry) => entry.method === method,
            );
            expect(validation).to.be.an('object');
            expect(validation.expectedOutcome).to.be.a('string').and.not.be
              .empty;
          });
        });
      },
    );
  });
});
