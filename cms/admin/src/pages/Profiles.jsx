import { useNavigate } from 'react-router-dom';
import { PageHeader } from '../components/Layout';
import ProfileData from './ProfileData';

/** All profiles from the database with a quality score and what is missing. */
export default function Profiles() {
  const navigate = useNavigate();
  return (
    <>
      <PageHeader
        title="Profiles"
        description="Every profile from the database with a quality score (0-100) and what is missing. Filter by category, year, month, score or problem; open a score to see where its points came from."
      />
      <div className="card p-5">
        <ProfileData onCheckLive={(paths) => navigate('/site-check', { state: { checkPaths: paths } })} />
      </div>
    </>
  );
}
