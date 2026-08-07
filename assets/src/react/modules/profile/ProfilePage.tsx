import { useEffect, useState } from 'react';
import { portalApi } from '../../api/portal';
import type { PortalProfile, UpdateProfileInput } from '../../api/types';

interface ProfilePageProps {
  onUpdated: (profile: PortalProfile) => void;
}

const emptyForm: UpdateProfileInput = {
  company: '',
  phone: '',
  country: '',
  timezone: '',
  language: '',
};

export function ProfilePage({ onUpdated }: ProfilePageProps) {
  const [profile, setProfile] = useState<PortalProfile | null>(null);
  const [form, setForm] = useState<UpdateProfileInput>(emptyForm);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);

  useEffect(() => {
    portalApi.profile()
      .then((data) => {
        setProfile(data);
        setForm({
          company: data.company ?? '',
          phone: data.phone ?? '',
          country: data.country ?? '',
          timezone: data.timezone ?? '',
          language: data.language ?? '',
        });
      })
      .catch(() => setError('Your profile could not be loaded.'));
  }, []);

  const field = (name: keyof UpdateProfileInput, value: string) => {
    setForm({ ...form, [name]: value });
    setSaved(false);
  };

  const submit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSaving(true);
    setSaved(false);
    setError(null);

    try {
      const updated = await portalApi.updateProfile(form);
      setProfile(updated);
      onUpdated(updated);
      setSaved(true);
    } catch (exception) {
      setError(exception instanceof Error ? exception.message : 'Profile could not be updated.');
    } finally {
      setSaving(false);
    }
  };

  if (!profile && !error) {
    return <p className="sbay-empty">Loading your profile…</p>;
  }

  return (
    <section className="sbay-page sbay-form-page">
      <header className="sbay-page__header">
        <div>
          <span className="sbay-kicker">Account details</span>
          <h1>Your profile</h1>
          <p>Keep your contact and regional information current.</p>
        </div>
      </header>

      {profile ? (
        <form className="sbay-ticket-form" onSubmit={submit}>
          <div className="sbay-readonly-profile">
            <strong>{profile.display_name || 'Customer'}</strong>
            <span>{profile.email || 'No email address'}</span>
            <small>Display name and email are managed by your WordPress account.</small>
          </div>
          <label>
            <span>Company</span>
            <input value={form.company} onChange={(event) => field('company', event.target.value)} maxLength={150} />
          </label>
          <label>
            <span>Phone</span>
            <input value={form.phone} onChange={(event) => field('phone', event.target.value)} maxLength={50} inputMode="tel" />
          </label>
          <label>
            <span>Country</span>
            <input value={form.country} onChange={(event) => field('country', event.target.value)} maxLength={100} />
          </label>
          <label>
            <span>Timezone <small>Example: Asia/Dhaka</small></span>
            <input value={form.timezone} onChange={(event) => field('timezone', event.target.value)} maxLength={100} />
          </label>
          <label>
            <span>Language <small>Example: en or en_US</small></span>
            <input value={form.language} onChange={(event) => field('language', event.target.value)} maxLength={20} />
          </label>
          {error ? <p className="sbay-form-error" role="alert">{error}</p> : null}
          {saved ? <p className="sbay-form-success" role="status">Profile saved.</p> : null}
          <button className="sbay-primary-button" type="submit" disabled={saving}>
            {saving ? 'Saving…' : 'Save profile'}
          </button>
        </form>
      ) : <p className="sbay-form-error" role="alert">{error}</p>}
    </section>
  );
}
