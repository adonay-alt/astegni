import { AdminRole } from '@/types/auth';

export type NavModule = 
  | 'dashboard'
  | 'curriculum'
  | 'national-exams'
  | 'tutors'
  | 'users'
  | 'marketing'
  | 'support'
  | 'settings';

/**
 * Normalizes database role strings ('tutor', 'tutor_moderator', 'academic', 'admin')
 */
export function normalizeRole(rawRole?: string | null): AdminRole {
  if (!rawRole) return 'academic_editor';
  const r = rawRole.toLowerCase().trim();

  if (r.includes('tutor')) return 'tutor_moderator';
  if (r.includes('academic') || r.includes('editor') || r.includes('curriculum')) return 'academic_editor';
  if (r.includes('support')) return 'support_moderator';
  if (r.includes('super') || r === 'admin') return 'super_admin';

  return 'academic_editor';
}

/**
 * STRICT MODULE PERMISSIONS:
 * Sub-roles do NOT have access to the Executive Dashboard.
 */
export const ROLE_PERMISSIONS: Record<AdminRole, string[]> = {
  super_admin: [
    'dashboard',
    'curriculum',
    'national-exams',
    'tutors',
    'users',
    'marketing',
    'support',
    'settings',
  ],
  academic_editor: [
    'curriculum',       // Academic Curriculum (LMS)
    'national-exams',   // National Exam Engine
  ],
  tutor_moderator: [
    'tutors',           // Tutor Marketplace ONLY
  ],
  support_moderator: [
    'support',          // Support Inquiries & Contact Settings ONLY
  ],
  support_agent: [
    'support',          // Backwards-compatible alias
  ],
  finance_auditor: [
    'settings',
  ],
};

export function hasModuleAccess(rawRole: string | undefined | null, module: string): boolean {
  const role = normalizeRole(rawRole);
  if (role === 'super_admin') return true;
  return ROLE_PERMISSIONS[role]?.includes(module) ?? false;
}

/**
 * Checks if a pathname is allowed for the user's role
 */
export function isRouteAllowed(rawRole: string | undefined | null, pathname: string): boolean {
  const role = normalizeRole(rawRole);
  if (role === 'super_admin') return true;

  if (pathname === '/' || pathname === '/dashboard' || pathname === '') {
    return hasModuleAccess(role, 'dashboard');
  }
  if (pathname.startsWith('/curriculum')) return hasModuleAccess(role, 'curriculum');
  if (pathname.startsWith('/national-exams')) return hasModuleAccess(role, 'national-exams');
  if (pathname.startsWith('/tutors')) return hasModuleAccess(role, 'tutors');
  if (pathname.startsWith('/users')) return hasModuleAccess(role, 'users');
  if (pathname.startsWith('/marketing')) return hasModuleAccess(role, 'marketing');
  
  // Support Inquiries & Contact Settings are grouped under 'support' access
  if (pathname.startsWith('/support') || pathname === '/settings/contact') {
    return hasModuleAccess(role, 'support');
  }
  if (pathname.startsWith('/settings')) return hasModuleAccess(role, 'settings');

  return true;
}

/**
 * Destination page when a user logs in:
 */
export function getDefaultRouteForRole(rawRole: string | undefined | null): string {
  const role = normalizeRole(rawRole);
  switch (role) {
    case 'tutor_moderator':
      return '/tutors';
    case 'academic_editor':
      return '/curriculum/grades';
    case 'support_moderator':
    case 'support_agent':
      return '/support/contacts';
    case 'finance_auditor':
      return '/settings/audit-logs';
    case 'super_admin':
    default:
      return '/';
  }
}
