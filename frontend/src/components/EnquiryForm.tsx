import { useMutation } from '@tanstack/react-query'
import { type FormEvent, type ReactNode, useId } from 'react'
import { submitEnquiry } from '../api/catalogue'
import { ApiError } from '../api/client'
import type { EnquiryInput, Product } from '../api/types'
import { ErrorMessage } from './StatusMessage'

export function EnquiryForm({ product }: { product: Product }) {
  const mutation = useMutation({ mutationFn: submitEnquiry })
  const formId = useId()

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const form = new FormData(event.currentTarget)
    const field = (name: string) => String(form.get(name) ?? '').trim()

    const enquiry: EnquiryInput = {
      productId: product.id,
      name: field('name'),
      email: field('email'),
      phone: field('phone') || undefined,
      message: field('message') || undefined,
    }

    mutation.mutate(enquiry)
  }

  if (mutation.isSuccess) {
    return (
      <div className="enquiry-thanks" role="status">
        <h2>Thank you, {mutation.variables.name}!</h2>
        <p>
          We have received your enquiry about <strong>{product.title}</strong>. Our sales team will reply to{' '}
          {mutation.variables.email} soon.
        </p>
      </div>
    )
  }

  // 422 responses carry per-field messages; anything else is shown as a single banner.
  const fieldErrors = mutation.error instanceof ApiError ? mutation.error.fieldErrors : {}
  const hasFieldErrors = Object.keys(fieldErrors).length > 0
  const errorFor = (name: string) => fieldErrors[name]?.[0]

  return (
    <form className="enquiry-form" onSubmit={handleSubmit} aria-labelledby={`${formId}-title`}>
      <h2 id={`${formId}-title`}>Enquire about this product</h2>

      {mutation.isError && !hasFieldErrors && <ErrorMessage error={mutation.error} />}

      <Field id={`${formId}-name`} label="Name" error={errorFor('name')}>
        {(a11y) => <input {...a11y} name="name" type="text" autoComplete="name" required maxLength={100} />}
      </Field>
      <Field id={`${formId}-email`} label="Email" error={errorFor('email')}>
        {(a11y) => <input {...a11y} name="email" type="email" autoComplete="email" required maxLength={254} />}
      </Field>
      <Field id={`${formId}-phone`} label="Phone (optional)" error={errorFor('phone')}>
        {(a11y) => <input {...a11y} name="phone" type="tel" autoComplete="tel" maxLength={30} />}
      </Field>
      <Field id={`${formId}-message`} label="Message (optional)" error={errorFor('message')}>
        {(a11y) => <textarea {...a11y} name="message" rows={4} maxLength={2000} />}
      </Field>

      <button type="submit" disabled={mutation.isPending}>
        {mutation.isPending ? 'Sending…' : 'Send enquiry'}
      </button>
    </form>
  )
}

interface ControlA11yProps {
  id: string
  'aria-invalid'?: true
  'aria-describedby'?: string
}

interface FieldProps {
  id: string
  label: string
  error?: string
  children: (a11y: ControlA11yProps) => ReactNode
}

/** Wires a label and an optional error message to a single form control. */
function Field({ id, label, error, children }: FieldProps) {
  const errorId = `${id}-error`

  return (
    <div className="field">
      <label htmlFor={id}>{label}</label>
      {children({
        id,
        'aria-invalid': error ? true : undefined,
        'aria-describedby': error ? errorId : undefined,
      })}
      {error && (
        <p id={errorId} className="field__error">
          {error}
        </p>
      )}
    </div>
  )
}
