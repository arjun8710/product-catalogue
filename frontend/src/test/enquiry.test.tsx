import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { ssd } from './fixtures'
import { categoriesRoute, mockApi, renderApp } from './utils'

async function openEnquiryForm(routes: Parameters<typeof mockApi>[0]) {
  const fetchMock = mockApi({ ...categoriesRoute(), 'GET /api/products/11': { data: ssd }, ...routes })
  const { user } = renderApp('/products/11')

  await user.click(await screen.findByRole('button', { name: 'Enquire' }))

  return { user, fetchMock }
}

describe('Enquiry', () => {
  it('submits contact details and shows a thank you message', async () => {
    let submitted: unknown
    const { user } = await openEnquiryForm({
      'POST /api/enquiries': ({ body }) => {
        submitted = body
        return { status: 202, body: { message: 'Enquiry received.' } }
      },
    })

    await user.type(screen.getByLabelText('Name'), 'Ada Lovelace')
    await user.type(screen.getByLabelText('Email'), 'ada@example.com')
    await user.type(screen.getByLabelText('Phone (optional)'), '+44 20 7946 0000')
    await user.type(screen.getByLabelText('Message (optional)'), 'Is this in stock?')
    await user.click(screen.getByRole('button', { name: 'Send enquiry' }))

    expect(await screen.findByRole('heading', { name: 'Thank you, Ada Lovelace!' })).toBeInTheDocument()
    expect(submitted).toEqual({
      productId: 11,
      name: 'Ada Lovelace',
      email: 'ada@example.com',
      phone: '+44 20 7946 0000',
      message: 'Is this in stock?',
    })
  })

  it('omits optional fields that were left blank', async () => {
    let submitted: unknown
    const { user } = await openEnquiryForm({
      'POST /api/enquiries': ({ body }) => {
        submitted = body
        return { status: 202 }
      },
    })

    await user.type(screen.getByLabelText('Name'), 'Ada')
    await user.type(screen.getByLabelText('Email'), 'ada@example.com')
    await user.click(screen.getByRole('button', { name: 'Send enquiry' }))

    await screen.findByRole('heading', { name: 'Thank you, Ada!' })
    expect(submitted).toEqual({ productId: 11, name: 'Ada', email: 'ada@example.com' })
  })

  it('shows validation errors from the server next to the relevant fields', async () => {
    const { user } = await openEnquiryForm({
      'POST /api/enquiries': () => ({
        status: 422,
        body: { message: 'Invalid', errors: { email: ['The email field must be a valid email address.'] } },
      }),
    })

    await user.type(screen.getByLabelText('Name'), 'Ada')
    await user.type(screen.getByLabelText('Email'), 'ada@example')
    await user.click(screen.getByRole('button', { name: 'Send enquiry' }))

    const email = screen.getByLabelText('Email')
    expect(await screen.findByText('The email field must be a valid email address.')).toBeInTheDocument()
    expect(email).toHaveAttribute('aria-invalid', 'true')
    expect(email).toHaveAccessibleDescription('The email field must be a valid email address.')
    expect(screen.queryByText(/Thank you/)).not.toBeInTheDocument()
  })

  it('keeps the form and shows an error when the enquiry cannot be sent', async () => {
    const { user } = await openEnquiryForm({
      'POST /api/enquiries': () => ({
        status: 503,
        body: { message: 'We could not send your enquiry right now. Please try again shortly.' },
      }),
    })

    await user.type(screen.getByLabelText('Name'), 'Ada')
    await user.type(screen.getByLabelText('Email'), 'ada@example.com')
    await user.click(screen.getByRole('button', { name: 'Send enquiry' }))

    expect(await screen.findByRole('alert')).toHaveTextContent('could not send your enquiry')
    expect(screen.getByLabelText('Name')).toHaveValue('Ada')
  })
})
