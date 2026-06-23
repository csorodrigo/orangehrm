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

describe('Leave- Configure - Work Week', function () {
  beforeEach(function () {
    cy.task('db:reset');
    cy.fixture('viewport').then(({HD}) => {
      cy.viewport(HD.width, HD.height);
    });
    cy.intercept('GET', '**/api/v2/leave/workweek*').as('getWorkWeek');
    cy.fixture('user').then(({admin}) => {
      this.user = admin;
    });
  });

  // Read
  describe('View Work Week', function () {
    it('Verify work week is loaded', function () {
      cy.loginTo(this.user, '/leave/defineWorkWeek');
      cy.wait('@getWorkWeek');
      cy.getOXD('pageTitle').should('include.text', 'Semana de trabalho');
    });
  });

  //Update
  describe('Update Work Week', function () {
    it('Update Work week with different combinations', function () {
      cy.loginTo(this.user, '/leave/defineWorkWeek');
      cy.wait('@getWorkWeek');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Segunda-feira').selectOption('Meio dia');
        cy.getOXDInput('Quarta-feira').selectOption('Dia não útil');
        cy.getOXDInput('Domingo').selectOption('Dia inteiro');
        cy.getOXD('button').contains('Salvar').click();
      });
      cy.toast('success', 'Salvo com sucesso');
    });

    it('Update Work week to all Half Days', function () {
      cy.loginTo(this.user, '/leave/defineWorkWeek');
      cy.wait('@getWorkWeek');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Segunda-feira').selectOption('Meio dia');
        cy.getOXDInput('Terça-feira').selectOption('Meio dia');
        cy.getOXDInput('Quarta-feira').selectOption('Meio dia');
        cy.getOXDInput('Quinta-feira').selectOption('Meio dia');
        cy.getOXDInput('Sexta-feira').selectOption('Meio dia');
        cy.getOXDInput('Sábado').selectOption('Meio dia');
        cy.getOXDInput('Domingo').selectOption('Meio dia');
        cy.getOXD('button').contains('Salvar').click();
      });
      cy.toast('success', 'Salvo com sucesso');
    });
  });

  //Validation
  describe('Work Week- Validations', function () {
    it('Required Validation', function () {
      cy.loginTo(this.user, '/leave/defineWorkWeek');
      cy.wait('@getWorkWeek');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Segunda-feira').selectOption('-- Selecionar --');
        cy.getOXDInput('Segunda-feira').isInvalid('Obrigatório');
      });
    });

    it('Work week  with all Non Working Days Validation', function () {
      cy.loginTo(this.user, '/leave/defineWorkWeek');
      cy.wait('@getWorkWeek');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Segunda-feira').selectOption('Dia não útil');
        cy.getOXDInput('Terça-feira').selectOption('Dia não útil');
        cy.getOXDInput('Quarta-feira').selectOption('Dia não útil');
        cy.getOXDInput('Quinta-feira').selectOption('Dia não útil');
        cy.getOXDInput('Sexta-feira').selectOption('Dia não útil');
        cy.getOXDInput('Sábado').selectOption('Dia não útil');
        cy.getOXDInput('Domingo').selectOption('Dia não útil');
        cy.getOXD('button').contains('Salvar').click();
      });
      cy.toast('warn', 'Pelo menos um dia deve ser um dia útil');
    });
  });
});
