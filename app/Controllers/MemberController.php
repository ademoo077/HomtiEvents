<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLog;
use App\Helpers\Database;
use App\Helpers\I18n;
use App\Helpers\Notification;
use App\Helpers\Rbac;
use App\Helpers\Session;
use App\Helpers\Validator;

/**
 * Membres d'association (Phase 7) :
 *   - gestion des membres et invitations côté association,
 *   - acceptation publique d'une invitation (création de compte membre
 *     ou rattachement d'un compte existant),
 *   - tableau de bord membre (événements de l'association).
 */
final class MemberController extends Controller
{
    private const INVITE_TTL = 7 * 24 * 3600;

    public function index(): never
    {
        $this->requirePermission('association.members');

        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);
        if ($associationId === 0) {
            abort(403, 'Aucune association liée à votre compte.');
        }

        $association = Database::one('SELECT * FROM associations WHERE id = ?', [$associationId]);
        if ($association === null) {
            abort(404, 'Association introuvable.');
        }

        $membres = Database::all(
            'SELECT id, nom, prenom, email, telephone, avatar, is_active, last_login, points, created_at
             FROM users
             WHERE association_id = ? AND role_user = ?
             ORDER BY prenom, nom',
            [$associationId, 'membre']
        );

        // Enrichir chaque membre : participations, badges, dernière activité
        foreach ($membres as &$m) {
            $userId = (int) $m['id'];
            $m['participations'] = (int) Database::value(
                'SELECT COUNT(*) FROM evenement_participant WHERE user_id = ?',
                [$userId]
            );
            $m['badges_count'] = (int) Database::value(
                'SELECT COUNT(*) FROM user_badges WHERE user_id = ?',
                [$userId]
            );
            $m['dernier_scan'] = Database::value(
                'SELECT MAX(heure_scan) FROM evenement_participant WHERE user_id = ?',
                [$userId]
            );
        }
        unset($m);

        $invitations = Database::all(
            'SELECT i.*, u.prenom AS inviteur_prenom, u.nom AS inviteur_nom
             FROM association_invitations i
             LEFT JOIN users u ON u.id = i.created_by
             WHERE i.association_id = ?
             ORDER BY i.created_at DESC',
            [$associationId]
        );

        // Stats globales
        $stats = [
            'total'          => count($membres),
            'actifs'         => count(array_filter($membres, static fn (array $m): bool => (int) ($m['is_active'] ?? 0) === 1)),
            'invitations'    => count($invitations),
            'participations' => (int) array_sum(array_column($membres, 'participations')),
        ];

        $this->view('association/members', [
            'association' => $association,
            'membres'     => $membres,
            'invitations' => $invitations,
            'stats'       => $stats,
        ], 'association');
    }

    public function invite(): never
    {
        $this->requirePermission('association.members');
        $this->csrfCheck();

        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);
        if ($associationId === 0) {
            abort(403, 'Aucune association liée à votre compte.');
        }

        $email = mb_strtolower(trim((string) input('email', '')));

        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|max:100',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), ['email' => $email]);
        }

        // Déjà membre ?
        $alreadyMember = Database::value(
            'SELECT COUNT(*) FROM users WHERE email = ? AND association_id = ?',
            [$email, $associationId]
        );
        if ((int) $alreadyMember > 0) {
            flash('error', __('members.already_member'));
            $this->redirect('association/membres');
        }

        // Invitation pending en double ?
        $pending = Database::value(
            "SELECT COUNT(*) FROM association_invitations
             WHERE association_id = ? AND LOWER(email) = ? AND statut = 'pending' AND (expires_at IS NULL OR expires_at > NOW())",
            [$associationId, $email]
        );
        if ((int) $pending > 0) {
            flash('error', __('members.invite_pending'));
            $this->redirect('association/membres');
        }

        $token = bin2hex(random_bytes(32));
        Database::insert('association_invitations', [
            'association_id' => $associationId,
            'email'          => $email,
            'token'          => $token,
            'statut'         => 'pending',
            'created_by'     => (int) ($user['id'] ?? 0),
            'expires_at'     => date('Y-m-d H:i:s', time() + self::INVITE_TTL),
        ]);

        AuditLog::log('association.invite', 'association_invitations', 0, null, ['email' => $email, 'association_id' => $associationId]);
        Notification::sendToAssociation(
            $associationId,
            __('members.invite_sent_title'),
            __('members.invite_sent_message', ['email' => $email]),
            'membre_invite',
            ['email' => $email]
        );

        flash('success', __('members.invite_created', ['email' => $email]));
        $this->redirect('association/membres');
    }

    /**
     * Créer directement un compte membre pour l'association.
     * L'admin association saisit : prénom, nom, email, téléphone (optionnel), mot de passe.
     */
    public function createMember(): never
    {
        $this->requirePermission('association.members');
        $this->csrfCheck();

        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);
        if ($associationId === 0) {
            abort(403, 'Aucune association liée à votre compte.');
        }

        $data = all_input();
        $validator = Validator::make($data, [
            'prenom'    => 'required|string|max:50',
            'nom'       => 'required|string|max:50',
            'email'     => 'required|email|max:100',
            'telephone' => 'nullable|string|max:20',
            'password'  => 'required|string|min:6|max:255',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data);
        }

        $email = mb_strtolower(trim((string) $data['email']));

        $exists = Database::value('SELECT COUNT(*) FROM users WHERE email = ?', [$email]);
        if ((int) $exists > 0) {
            flash('error', $isAr = I18n::direction() === 'rtl'
                ? 'هذا البريد الإلكتروني مسجل بالفعل.'
                : 'Un compte existe déjà avec cet email.');
            $this->redirect('association/membres');
        }

        $userId = Database::insert('users', [
            'prenom'        => trim((string) $data['prenom']),
            'nom'           => trim((string) $data['nom']),
            'email'         => $email,
            'password'      => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            'telephone'     => ! empty($data['telephone']) ? trim((string) $data['telephone']) : null,
            'role_user'     => 'membre',
            'association_id'=> $associationId,
            'is_active'     => 1,
            'points'        => 0,
        ]);

        AuditLog::log('association.create_member', 'users', $userId, null, [
            'email'         => $email,
            'association_id'=> $associationId,
        ]);

        Notification::sendToAssociation(
            $associationId,
            $isAr ? 'تم إنشاء حساب عضو' : 'Compte membre créé',
            $isAr
                ? 'تم إنشاء حساب "' . trim((string) $data['prenom']) . ' ' . trim((string) $data['nom']) . '" بنجاح.'
                : 'Le compte de "' . trim((string) $data['prenom']) . ' ' . trim((string) $data['nom']) . '" a été créé avec succès.',
            'membre_created',
            ['user_id' => $userId, 'email' => $email]
        );

        flash('success', $isAr
            ? 'تم إنشاء الحساب بنجاح: ' . $email
            : 'Compte créé avec succès : ' . $email);
        $this->redirect('association/membres');
    }

    public function revoke(string $id): never
    {
        $this->requirePermission('association.members');
        $this->csrfCheck();

        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);

        Database::run(
            "UPDATE association_invitations SET statut = 'revoked'
             WHERE id = ? AND association_id = ? AND statut = 'pending'",
            [(int) $id, $associationId]
        );

        AuditLog::log('association.invite_revoke', 'association_invitations', (int) $id);

        flash('success', __('members.invite_revoked'));
        $this->redirect('association/membres');
    }

    public function remove(string $id): never
    {
        $this->requirePermission('association.members');
        $this->csrfCheck();

        $user = $this->user();
        $associationId = (int) ($user['association_id'] ?? 0);

        $membre = Database::one(
            'SELECT id, nom, prenom, email FROM users
             WHERE id = ? AND association_id = ? AND role_user = ?',
            [(int) $id, $associationId, 'membre']
        );
        if ($membre === null) {
            abort(404, 'Membre introuvable.');
        }

        // Démotion en citoyen (conservation du compte et de son historique).
        Database::update('users', [
            'role_user'      => 'citoyen',
            'association_id' => null,
        ], 'id = ?', [(int) $id]);

        AuditLog::log('association.member_remove', 'users', (int) $id, null, ['association_id' => $associationId]);
        Notification::send(
            (int) $id,
            __('members.removed_title'),
            __('members.removed_message', ['association' => (string) ($membre['prenom'] ?? '')]),
            'membre_retire'
        );

        flash('success', __('members.member_removed', ['nom' => trim((string) ($membre['prenom'] . ' ' . $membre['nom']))]));
        $this->redirect('association/membres');
    }

    public function acceptShow(string $token): never
    {
        $invitation = $this->findValidInvitation($token);
        if ($invitation === null) {
            abort(404, __('members.invite_invalid'));
        }

        $existing = Database::one('SELECT id, prenom, nom FROM users WHERE email = ?', [$invitation['email']]);

        $association = Database::one(
            'SELECT nom FROM associations WHERE id = ?',
            [(int) $invitation['association_id']]
        );

        $this->view('member/accept', [
            'invitation'  => $invitation,
            'association' => $association,
            'existing'    => $existing,
        ], 'guest');
    }

    public function accept(string $token): never
    {
        $this->csrfCheck();

        $invitation = $this->findValidInvitation($token);
        if ($invitation === null) {
            abort(404, __('members.invite_invalid'));
        }

        $associationId = (int) $invitation['association_id'];
        $email         = (string) $invitation['email'];
        $existingUser  = Database::one('SELECT id, role_user FROM users WHERE email = ?', [$email]);

        if ($existingUser !== null) {
            $this->attachExistingUser($invitation, (int) $existingUser['id'], (string) $existingUser['role_user']);

            flash('success', __('members.invite_accepted'));
            $this->redirect(dashboard_path());
        }

        $data = [
            'prenom'    => trim((string) input('prenom', '')),
            'nom'       => trim((string) input('nom', '')),
            'telephone' => trim((string) input('telephone', '')),
            'password'  => (string) input('password', ''),
        ];

        $validator = Validator::make($data, [
            'prenom'    => 'required|string|max:50',
            'nom'       => 'required|string|max:50',
            'telephone' => 'nullable|phone',
            'password'  => 'required|min:8',
        ]);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), array_merge($data, ['token' => $token]));
        }

        $userId = Database::insert('users', [
            'nom'            => $data['nom'],
            'prenom'         => $data['prenom'],
            'email'          => $email,
            'password'       => password_hash($data['password'], PASSWORD_BCRYPT),
            'role_user'      => 'membre',
            'telephone'      => $data['telephone'] !== '' ? $data['telephone'] : null,
            'association_id' => $associationId,
            'is_active'      => 1,
        ]);

        Database::update('association_invitations', [
            'statut'      => 'accepted',
            'accepted_by' => $userId,
            'accepted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $invitation['id']]);

        AuditLog::log('association.invite_accepted', 'users', $userId, null, ['association_id' => $associationId]);
        Notification::sendToAssociation(
            $associationId,
            __('members.accepted_title'),
            __('members.accepted_message', ['email' => $email]),
            'membre_accepte',
            ['email' => $email]
        );

        Session::login($userId);
        Session::set('user', Database::one('SELECT * FROM users WHERE id = ?', [$userId]));
        Session::set('user_roles', ['membre']);
        Rbac::loadPermissions($userId);

        flash('success', __('members.welcome_member'));
        $this->redirect(dashboard_path());
    }

    public function dashboard(): never
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'membre') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);

        $events = $associationId > 0
            ? Database::all(
                'SELECT e.*, c.nom AS commune_nom, a.nom AS association_nom,
                        (SELECT COUNT(*) FROM evenement_participant ep WHERE ep.evenement_id = e.id) AS participants
                 FROM evenements e
                 LEFT JOIN commune c ON c.id = e.commune_id
                 LEFT JOIN associations a ON a.id = e.association_id
                 WHERE e.association_id = ? AND e.deleted_at IS NULL
                 ORDER BY e.date_evenement DESC, e.created_at DESC',
                [$associationId]
            )
            : [];

        $association = $associationId > 0
            ? Database::one('SELECT * FROM associations WHERE id = ?', [$associationId])
            : null;

        // ── Enrichissement & indicateurs ─────────────────────────────────
        $today   = date('Y-m-d');
        $now     = time();
        $isAr    = I18n::direction() === 'rtl';
        $days = static function (?string $date, ?string $heure) use ($now): ?int {
            if ($date === null || $date === '') {
                return null;
            }
            $ts = strtotime((string) $date . (empty($heure) ? '' : ' ' . substr((string) $heure, 0, 5)));
            if ($ts === false) {
                return null;
            }
            return (int) floor(($ts - $now) / 86400);
        };

        $participantsTotal = 0;
        foreach ($events as &$ev) {
            $p   = (int) ($ev['participants'] ?? 0);
            $cap = ($ev['capacite'] ?? null) !== null ? (int) $ev['capacite'] : null;
            $ev['participants']       = $p;
            $participantsTotal       += $p;
            $ev['places_restantes']   = $cap !== null ? max(0, $cap - $p) : null;
            $ev['taux_remplissage']   = $cap !== null ? min(100, (int) round($p / max(1, $cap) * 100)) : null;
            $ev['jours_restants']     = $days((string) ($ev['date_evenement'] ?? ''), (string) ($ev['heure'] ?? ''));
        }
        unset($ev);

        $prochains = array_values(array_filter(
            $events,
            static fn (array $e): bool => (string) ($e['date_evenement'] ?? '') >= $today
        ));
        usort($prochains, static fn (array $a, array $b): int => strcmp((string) $a['date_evenement'], (string) $b['date_evenement']));
        $todayEvents  = array_values(array_filter($prochains, static fn (array $e): bool => (string) ($e['date_evenement'] ?? '') === $today));
        $enCours      = array_values(array_filter($events, static fn (array $e): bool => strtoupper((string) ($e['statut'] ?? '')) === 'EN_COURS'));
        $aCorriger    = array_values(array_filter($events, static fn (array $e): bool => in_array(strtoupper((string) ($e['statut'] ?? '')), ['REFUSE', 'MODIFICATION_DEMANDEE'], true)));
        $prochain     = $prochains[0] ?? null;
        $mesParticipations = (int) Database::value(
            'SELECT COUNT(*) FROM evenement_participant WHERE user_id = ?',
            [(int) ($user['id'] ?? 0)]
        );

        // ── Idées & conseils contextuels ─────────────────────────────────
        $suggestions = [];

        if ($events === []) {
            $suggestions[] = [
                'icon'  => 'mdi-calendar-plus-outline',
                'color' => 'gray',
                'titre' => $isAr ? 'لا توجد فعاليات بعد' : 'Aucun événement programmé',
                'texte' => $isAr
                    ? 'لا توجد فعاليات بعد لجمعيتكم. تكلّموا مع المشرفين لبرمجة الفعاليات القادمة.'
                    : 'Votre association n\'a encore rien programmé. Échangez avec ses responsables pour planifier les prochains événements.',
            ];
        } else {
            // Aujourd'hui
            if ($todayEvents !== []) {
                $ev = $todayEvents[0];
                $suggestions[] = [
                    'icon'  => 'mdi-calendar-today',
                    'color' => 'amber',
                    'titre' => $isAr ? 'فعالية اليوم' : 'Événement aujourd\'hui',
                    'texte' => $isAr
                        ? 'لا تنسوا فعالية "' . (string) ($ev['adresse'] ?? '') . '"'
                          . (empty($ev['heure']) ? '' : ' في ' . substr((string) $ev['heure'], 0, 5)) . '.'
                        : 'N\'oubliez pas "' . (string) ($ev['adresse'] ?? '') . '"'
                          . (empty($ev['heure']) ? '' : ' à ' . substr((string) $ev['heure'], 0, 5)) . '.'
                          . ' Soyez présents et invitez votre entourage !',
                    'lien' => url('dashboard') . '#evenements',
                    'cta'  => $isAr ? 'عرض الفعالية' : 'Voir l\'événement',
                ];
            }

            // Dernière ligne droite (J-1)
            if ($prochain !== null && $todayEvents === []) {
                $j = $prochain['jours_restants'] ?? null;
                if ($j === 0) {
                    $texte = $isAr
                        ? 'فعالية "' . (string) ($prochain['adresse'] ?? '') . '" اليوم'
                          . (empty($prochain['heure']) ? '' : ' في ' . substr((string) $prochain['heure'], 0, 5)) . '.'
                        : 'L\'événement "' . (string) ($prochain['adresse'] ?? '') . '" est prévu aujourd\'hui'
                          . (empty($prochain['heure']) ? '' : ' à ' . substr((string) $prochain['heure'], 0, 5)) . '.';
                    $texte .= $isAr ? ' حان الوقت للتحضير!' : ' Il est temps de se préparer !';
                    $suggestions[] = [
                        'icon'  => 'mdi-alarm',
                        'color' => 'amber',
                        'titre' => $isAr ? 'اليوم الكبير!' : 'C\'est aujourd\'hui !',
                        'texte' => $texte,
                        'lien'  => url('dashboard') . '#evenements',
                        'cta'   => $isAr ? 'عرض الفعالية' : 'Voir l\'événement',
                    ];
                } elseif ($j === 1) {
                    $suggestions[] = [
                        'icon'  => 'mdi-calendar-edit-outline',
                        'color' => 'amber',
                        'titre' => $isAr ? 'غداً: ' . (string) ($prochain['adresse'] ?? '') : 'Demain : ' . (string) ($prochain['adresse'] ?? ''),
                        'texte' => $isAr
                            ? 'لديكم يوم واحد للاستعداد. أكّدوا حضوركم وشاركوا الفعالية مع محيطكم.'
                            : 'Plus qu\'un jour avant "' . (string) ($prochain['adresse'] ?? '') . '". Préparez-vous et relayez l\'information.',
                        'lien' => url('dashboard') . '#evenements',
                        'cta'  => $isAr ? 'عرض الفعالية' : 'Voir l\'événement',
                    ];
                } else {
                    $suggestions[] = [
                        'icon'  => 'mdi-calendar-clock-outline',
                        'color' => 'primary',
                        'titre' => $isAr ? 'حدث قادم: ' . (string) ($prochain['adresse'] ?? '') : 'Événement à venir : ' . (string) ($prochain['adresse'] ?? ''),
                        'texte' => $isAr
                            ? 'في ' . date('d/m/Y', strtotime((string) $prochain['date_evenement']))
                              . (empty($prochain['heure']) ? '' : ' على الساعة ' . substr((string) $prochain['heure'], 0, 5))
                              . ' — أدعُ محيطك وشارك الفعالية.'
                            : 'Le ' . date('d/m/Y', strtotime((string) $prochain['date_evenement']))
                              . (empty($prochain['heure']) ? '' : ' à ' . substr((string) $prochain['heure'], 0, 5))
                              . ' — invitez votre entourage et relayez l\'événement.',
                        'lien' => url('dashboard') . '#evenements',
                        'cta'  => $isAr ? 'عرض الفعالية' : 'Voir l\'événement',
                    ];
                }
            }

            // Événement en cours
            if ($enCours !== []) {
                $ev = $enCours[0];
                $suggestions[] = [
                    'icon'  => 'mdi-broadcast',
                    'color' => 'green',
                    'titre' => $isAr ? 'فعالية جارية الآن' : 'Un événement se déroule maintenant',
                    'texte' => $isAr
                        ? 'فعالية "' . (string) ($ev['adresse'] ?? '') . '" مفتوحة الآن — شجّعوا الحضور.'
                        : '"' . (string) ($ev['adresse'] ?? '') . '" est ouvert — encouragez la participation.'
                          . ' Chaque scan compte pour l\'association.',
                    'lien' => url('dashboard') . '#evenements',
                    'cta'  => $isAr ? 'عرض التفاصيل' : 'Voir les détails',
                ];
            }

            // Capacité presque atteinte
            if ($prochain !== null && ($prochain['taux_remplissage'] ?? null) !== null && (int) $prochain['taux_remplissage'] >= 85) {
                $suggestions[] = [
                    'icon'  => 'mdi-account-group',
                    'color' => 'red',
                    'titre' => $isAr ? 'المقاعد توشك على النفاد' : 'Places presque épuisées',
                    'texte' => $isAr
                        ? 'الحدث القادم ممتلئ بنسبة ' . (int) $prochain['taux_remplissage'] . '% — طوّروا الترويج!'
                        : 'Le prochain événement est rempli à ' . (int) $prochain['taux_remplissage'] . '% — accentuez la communication !',
                ];
            }

            // Capacité non définie
            if ($prochain !== null && ($prochain['capacite'] ?? null) === null) {
                $suggestions[] = [
                    'icon'  => 'mdi-account-multiple-plus-outline',
                    'color' => 'gray',
                    'titre' => $isAr ? 'بدون سعة محددة' : 'Capacité non définie',
                    'texte' => $isAr
                        ? 'لم تُحدّد سعة الحدث القادم. اقترحوا على الإدارة تحديد سعة لتتبّع المشاركة.'
                        : 'La capacité du prochain événement n\'est pas définie. Suggérez à l\'association de la renseigner pour mieux suivre la participation.',
                ];
            }

            // Événement à corriger
            if ($aCorriger !== []) {
                $ev = $aCorriger[0];
                $suggestions[] = [
                    'icon'  => 'mdi-file-alert-outline',
                    'color' => 'red',
                    'titre' => $isAr ? 'فعالية بانتظار التصحيح' : 'Événement à corriger',
                    'texte' => $isAr
                        ? 'الفعالية "' . (string) ($ev['adresse'] ?? '') . '" تحتاج إلى تصحيح من قبل الإدارة.'
                        : 'L\'événement "' . (string) ($ev['adresse'] ?? '') . '" nécessite des corrections avant validation.',
                    'lien' => url('dashboard') . '#evenements',
                    'cta'  => $isAr ? 'عرض التفاصيل' : 'Voir les détails',
                ];
            }
        }

        if ($participantsTotal > 0) {
            $suggestions[] = [
                'icon'  => 'mdi-account-group-outline',
                'color' => 'success',
                'titre' => $isAr
                    ? (string) $participantsTotal . ' مشارك إجمالاً'
                    : $participantsTotal . ' participant(s) au total',
                'texte' => $isAr
                    ? 'فعالياتكم تحشد المجتمع. واصلوا الترويج للتواريخ القادمة.'
                    : 'Vos événements mobilisent la communauté. Continuez à relayer les prochaines dates.',
            ];
        } elseif ($events !== []) {
            $suggestions[] = [
                'icon'  => 'mdi-account-multiple-outline',
                'color' => 'gray',
                'titre' => $isAr ? 'بداية التعبئة' : 'Lancez la mobilisation',
                'texte' => $isAr
                    ? 'لم يُسجَّل أي مشارك بعد. شاركوا الفعاليات القادمة مع سكان البلدية.'
                    : 'Aucune participation enregistrée pour l\'instant. Faites connaître vos événements aux habitants de la commune.',
            ];
        }

        // Première participation du membre
        if ($mesParticipations === 0 && $events !== []) {
            $suggestions[] = [
                'icon'  => 'mdi-qrcode-scan',
                'color' => 'blue',
                'titre' => $isAr ? 'مشاركتكم الأولى' : 'Votre première participation',
                'texte' => $isAr
                    ? 'احضروا إلى الفعالية القادمة وسجّلوا حضوركم لحساب مساهماتكم.'
                    : 'Présentez-vous au prochain événement et faites scanner votre QR pour comptabiliser votre participation.',
            ];
        }

        // Profil incomplet
        if (empty($user['telephone']) || empty($user['avatar'])) {
            $suggestions[] = [
                'icon'  => 'mdi-account-edit-outline',
                'color' => 'amber',
                'titre' => $isAr ? 'أكملوا ملفكم الشخصي' : 'Complétez votre profil',
                'texte' => $isAr
                    ? 'أضيفوا رقم الهاتف أو صورة للملف لتوثيق عضويتكم.'
                    : 'Ajoutez votre téléphone ou une photo de profil pour compléter votre adhésion.',
                'lien' => url('profile'),
                'cta'  => $isAr ? 'تعديل الملف' : 'Modifier mon profil',
            ];
        }

        // ── Stats personnelles du membre ──────────────────────────────────
        $userId = (int) ($user['id'] ?? 0);
        $mesPoints = (int) Database::value('SELECT points FROM users WHERE id = ?', [$userId]);
        $mesBadges = Database::all(
            'SELECT b.nom, b.description, b.icone, b.couleur, ub.date_obtention
             FROM badges b JOIN user_badges ub ON ub.badge_id = b.id
             WHERE ub.user_id = ? ORDER BY ub.date_obtention DESC',
            [$userId]
        );
        $dernieresParticipations = Database::all(
            'SELECT e.id, e.adresse, e.date_evenement, e.heure, c.nom AS commune_nom, ep.heure_scan
             FROM evenement_participant ep
             JOIN evenements e ON e.id = ep.evenement_id
             LEFT JOIN commune c ON c.id = e.commune_id
             WHERE ep.user_id = ?
             ORDER BY ep.heure_scan DESC LIMIT 5',
            [$userId]
        );

        // Journal d'activité récente
        $activites = Database::all(
            "SELECT action, modele, modele_id, created_at
             FROM audit_logs
             WHERE user_id = ?
             ORDER BY created_at DESC LIMIT 15",
            [$userId]
        );
        $activites = array_map(static function (array $a): array {
            $icon = match (true) {
                str_contains($a['action'], 'scan') || str_contains($a['action'], 'presence') => 'mdi-qrcode-scan',
                str_contains($a['action'], 'login') || str_contains($a['action'], 'connexion') => 'mdi-login',
                str_contains($a['action'], 'create') => 'mdi-plus-circle',
                str_contains($a['action'], 'badge') => 'mdi-trophy',
                str_contains($a['action'], 'invite') || str_contains($a['action'], 'invitation') => 'mdi-account-plus',
                str_contains($a['action'], 'validate') || str_contains($a['action'], 'valide') => 'mdi-check-circle',
                default => 'mdi-circle-small',
            };
            $cssClass = match (true) {
                str_contains($a['action'], 'scan') || str_contains($a['action'], 'presence') => 'wh-activity-scan',
                str_contains($a['action'], 'login') || str_contains($a['action'], 'connexion') => 'wh-activity-login',
                str_contains($a['action'], 'badge') => 'wh-activity-badge',
                str_contains($a['action'], 'create') => 'wh-activity-create',
                default => '',
            };
            $label = $a['action'] . ($a['modele'] !== '' ? ' (' . $a['modele'] . '#' . ($a['modele_id'] ?? '') . ')' : '');
            return [
                'icon'      => $icon,
                'cssClass'  => $cssClass,
                'label'     => $label,
                'date'      => $a['created_at'],
            ];
        }, $activites);

        $this->view('member/dashboard', [
            'association'        => $association,
            'events'             => $events,
            'prochains'          => $prochains,
            'passes'             => array_values(array_filter(
                $events,
                static fn (array $e): bool => (string) ($e['date_evenement'] ?? '') < $today
            )),
            'prochain'           => $prochain,
            'suggestions'        => $suggestions,
            'kpis'               => [
                'total'        => count($events),
                'prochains'    => count($prochains),
                'aujourdhui'   => count($todayEvents),
                'en_cours'     => count($enCours),
                'participants' => $participantsTotal,
            ],
            'mesPoints'               => $mesPoints,
            'mesBadges'               => $mesBadges,
            'mesParticipations'       => $mesParticipations,
            'dernieresParticipations' => $dernieresParticipations,
            'activites'               => $activites,
        ], 'member');
    }

    /**
     * Page de scan QR pour les membres de l'association.
     * Les membres scannent les QR codes des participants à l'entrée des événements.
     */
    public function scan(): never
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'membre') {
            abort(403, 'Accès refusé.');
        }

        $associationId = (int) ($user['association_id'] ?? 0);

        $evenements = Database::all(
            'SELECT e.id, e.adresse, e.date_evenement, e.statut, e.heure,
                    q.token_qr, q.date_expiration
             FROM evenements e
             LEFT JOIN qr_event q ON q.evenement_id = e.id
             WHERE e.deleted_at IS NULL
               AND e.association_id = ?
               AND e.statut IN (\'PROGRAMME\', \'QR_GENERE\', \'EN_COURS\')
               AND q.token_qr IS NOT NULL
             ORDER BY e.date_evenement DESC LIMIT 20',
            [$associationId]
        );

        $this->view('qrcode/scan_optimized', [
            'evenements' => $evenements,
            'memberScan' => true,
        ], 'member');
    }

    /**
     * Notifications pour les membres.
     */
    public function notifications(): never
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'membre') {
            abort(403, 'Accès refusé.');
        }

        $notifications = \App\Helpers\Notification::recent((int) $user['id'], 50);
        $unreadCount = \App\Helpers\Notification::unreadCount((int) $user['id']);

        $this->view('member/notifications', [
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
        ], 'member');
    }

    /**
     * Page des participations du membre — utilise le layout member.
     */
    public function participations(): never
    {
        $this->requireAuth();

        $user = $this->user();
        if ($user === null || Rbac::role($user) !== 'membre') {
            abort(403, 'Accès refusé.');
        }

        $userId = (int) $user['id'];

        $participations = Database::all(
            'SELECT e.id AS evenement_id, e.adresse, e.date_evenement, e.heure, e.statut AS event_statut,
                    ep.heure_scan, ep.ip_address,
                    c.nom AS commune_nom,
                    a.id AS album_id, a.titre AS album_titre,
                    (SELECT COUNT(*) FROM photos p WHERE p.album_id = a.id AND p.status = ?) AS nb_photos
             FROM evenement_participant ep
             JOIN evenements e ON e.id = ep.evenement_id AND e.deleted_at IS NULL
             LEFT JOIN commune c ON c.id = e.commune_id
             LEFT JOIN albums a ON a.evenement_id = e.id AND a.statut = ?
             WHERE ep.user_id = ?
             ORDER BY ep.heure_scan DESC',
            ['publie', 'active', $userId]
        );

        $this->view('member/participations', [
            'participations' => $participations,
        ], 'member');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findValidInvitation(string $token): ?array
    {
        if (strlen($token) !== 64 || ! ctype_xdigit($token)) {
            return null;
        }

        $invitation = Database::one(
            'SELECT * FROM association_invitations WHERE token = ?',
            [$token]
        );
        if ($invitation === null || $invitation['statut'] !== 'pending') {
            return null;
        }

        if (($invitation['expires_at'] ?? null) !== null && strtotime((string) $invitation['expires_at']) < time()) {
            return null;
        }

        return $invitation;
    }

    /**
     * @param array<string, mixed> $invitation
     */
    private function attachExistingUser(array $invitation, int $userId, string $currentRole): void
    {
        $associationId = (int) $invitation['association_id'];

        // Un compte non-citoyen déjà rattaché à une autre structure : refus.
        $holder = Database::value('SELECT association_id FROM users WHERE id = ?', [$userId]);
        if (! in_array($currentRole, ['citoyen', 'membre'], true) || (int) ($holder ?? 0) > 0) {
            abort(409, __('members.invite_conflict'));
        }

        Database::update('users', [
            'role_user'      => 'membre',
            'association_id' => $associationId,
        ], 'id = ?', [$userId]);

        Database::update('association_invitations', [
            'statut'      => 'accepted',
            'accepted_by' => $userId,
            'accepted_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $invitation['id']]);

        AuditLog::log('association.invite_accepted', 'users', $userId, null, ['association_id' => $associationId]);
        Notification::sendToAssociation(
            $associationId,
            __('members.accepted_title'),
            __('members.accepted_message', ['email' => (string) $invitation['email']]),
            'membre_accepte',
            ['email' => $invitation['email']]
        );

        Session::refreshUser();
        Session::set('user_roles', ['membre']);
        Rbac::loadPermissions($userId);
    }
}
