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

describe('Admin - Job Category', function () {
  beforeEach(function () {
    cy.task('db:reset');
    cy.fixture('viewport').then(({HD}) => {
      cy.viewport(HD.width, HD.height);
    });
    cy.fixture('chars').as('strings');
    cy.intercept('GET', '**/api/v2/admin/job-categories*').as(
      'getJobCategories',
    );
    cy.intercept('POST', '**/api/v2/admin/job-categories').as(
      'postJobCategories',
    );
    cy.intercept('PUT', '**/api/v2/admin/job-categories/*').as(
      'updateJobCategories',
    );
    cy.intercept('DELETE', '**/api/v2/admin/job-categories').as(
      'deleteJobCategories',
    );
    cy.fixture('user').then(({admin}) => {
      this.user = admin;
    });
  });

  // Read
  describe('list job Categories', function () {
    it('job title list is loaded', function () {
      cy.loginTo(this.user, '/admin/jobCategory');
      cy.wait('@getJobCategories');
      cy.getOXD('numRecords').contains('(9) registros encontrados');
    });
  });
  // Create
  describe('Add job Category', function () {
    it('add job category', function () {
      cy.loginTo(this.user, '/admin/saveJobCategory');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Nome').type(this.strings.chars50.text);
        cy.getOXD('button').contains('Salvar').click();
      });
      cy.wait('@postJobCategories');
      cy.toast('success', 'Salvo com sucesso');
    });
    it('Job Category form validations', function () {
      cy.loginTo(this.user, '/admin/saveJobCategory');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Nome').then(($input) => {
          cy.wrap($input).type(this.strings.chars200.text);
          cy.wrap($input).isInvalid('Não deve exceder caracteres50');
          cy.wrap($input).setValue('');
          cy.wrap($input).isInvalid('Obrigatório');
          cy.wrap($input).type('Craft Workers');
          cy.wrap($input).isInvalid('Já existe');
        });
      });
    });
    it('add a job category and click cancel', function () {
      cy.loginTo(this.user, '/admin/saveJobCategory');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Nome').type(this.strings.chars50.text);
        cy.getOXD('button').contains('Cancelar').click();
      });
      cy.wait('@getJobCategories');
      cy.getOXD('pageTitle').contains('Categorias de cargo');
    });
  });
  //Update
  describe('Update job Category', function () {
    it('Edit job category', function () {
      cy.loginTo(this.user, '/admin/saveJobCategory/1');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Nome').then(($input) => {
          cy.wrap($input).clear();
          cy.wrap($input).type(this.strings.chars50.text);
        });
        cy.getOXD('button').contains('Salvar').click();
      });
      cy.wait('@updateJobCategories');
      cy.toast('success', 'Atualizado com sucesso');
    });
  });
  //Delete
  describe('Delete job Category', function () {
    it('Delete a single job category', function () {
      cy.loginTo(this.user, '/admin/jobCategory');
      cy.wait('@getJobCategories');
      cy.get(
        '.oxd-table-body > :nth-child(1) .oxd-table-cell-actions > :nth-child(1)',
      ).click();
      cy.getOXD('button').contains('Sim, excluir').click();
      cy.wait('@getJobCategories');
      cy.toast('success', 'Excluído com sucesso');
    });
    it('Bulk Delete job categories', function () {
      cy.loginTo(this.user, '/admin/jobCategory');
      cy.wait('@getJobCategories');
      cy.get('.oxd-table-header .oxd-checkbox-input').click();
      cy.get('.oxd-button--label-danger.cia-ferias-horizontal-margin').click();
      cy.getOXD('button').contains('Sim, excluir').click();
      cy.wait('@getJobCategories');
      cy.toast('success', 'Excluído com sucesso');
    });
  });
});
