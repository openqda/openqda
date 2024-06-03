/// <reference types="cypress" />

// Welcome to Cypress!
//
// This spec file contains a variety of sample tests
// for a todo list app that are designed to demonstrate
// the power of writing tests in Cypress.
//
// To learn more about how Cypress works and
// what makes it such an awesome testing tool,
// please read our getting started guide:
// https://on.cypress.io/introduction-to-cypress
import login from '../../fixtures/login.json'

describe('open qda welcome page', () => {
    beforeEach(() => {
        // Cypress starts out with a blank slate for each test
        // so we must tell it to visit our website with the `cy.visit()` command.
        // Since we want to visit the same URL at the start of all our tests,
        // we include it in our beforeEach function so that it runs before each test
        cy.visit(Cypress.env('host'))
    })

    it('displays the welcome screen by default', () => {
        cy.get('.oqda-owl-logo', { timeout: 3000 }).should('have.length', 1)
        cy.get('.register-btn', { timeout: 3000 }).should('have.length', 1)
    })

    context('signing in', () => {
        beforeEach(() => {
            const signInBtn = cy.get('.sign-in-btn')
            signInBtn.should('have.length', 1)
            signInBtn.click()
        })

    it('rejects a non-existent user', () => {


        const emailInp = cy.get('#email', { timeout: 3000 })
        emailInp.should('have.length', 1)
        emailInp.type('foo@bar.com')

        const passwInp = cy.get('#password', { timeout: 3000 })
        emailInp.should('have.length', 1)
        emailInp.type('password')

        cy.get('[type="submit"]').click()

        const status = cy.get('.email-error', { timeout: 3000 })
       status.children('p').should('have.text', 'These credentials do not match our records.')
    })

    it('signs in the user', () => {
        const emailInp = cy.get('#email', { timeout: 3000 })
        emailInp.should('have.length', 1)
        emailInp.type(login.email)

        const passwInp = cy.get('#password', { timeout: 3000 })
        emailInp.should('have.length', 1)
        emailInp.type(login.password)

        cy.get('[type="submit"]').click()
    })
    })
})
