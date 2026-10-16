function Base({ size = 24, children, ...props }) {
    return (
        <svg
            width={size}
            height={size}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={1.7}
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
            {...props}
        >
            {children}
        </svg>
    );
}

export function HomeIcon(props) {
    return (
        <Base {...props}>
            <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5.2v-6.2H10.2V21H5a1 1 0 0 1-1-1v-9.5Z" />
        </Base>
    );
}

export function SearchIcon(props) {
    return (
        <Base {...props}>
            <circle cx="11" cy="11" r="6.5" />
            <path d="m16.2 16.2 4.3 4.3" />
        </Base>
    );
}

export function CompassIcon(props) {
    return (
        <Base {...props}>
            <circle cx="12" cy="12" r="9" />
            <path d="m14.7 9.3-1.4 5.4-5.4 1.4 1.4-5.4 5.4-1.4Z" />
        </Base>
    );
}

export function PlaneIcon(props) {
    return (
        <Base {...props}>
            <path d="M21 4 10.5 14.5" />
            <path d="M21 4 14.2 21l-3.7-6.5L4 10.8 21 4Z" />
        </Base>
    );
}

export function PeopleIcon(props) {
    return (
        <Base {...props}>
            <circle cx="9" cy="8" r="3.2" />
            <path d="M3.6 19.2c.4-3 2.6-4.7 5.4-4.7s5 1.7 5.4 4.7" />
            <circle cx="16.4" cy="8.6" r="2.5" />
            <path d="M15.2 14.6c2.3.3 4.2 1.8 4.8 4.6" />
        </Base>
    );
}

export function LogoutIcon(props) {
    return (
        <Base {...props}>
            <path d="M10 21H6.5A1.5 1.5 0 0 1 5 19.5v-15A1.5 1.5 0 0 1 6.5 3H10" />
            <path d="M15 16.5 20 12l-5-4.5" />
            <path d="M20 12H10" />
        </Base>
    );
}

export function HeartIcon({ filled = false, ...props }) {
    return (
        <Base {...props} fill={filled ? 'currentColor' : 'none'}>
            <path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z" />
        </Base>
    );
}

export function PlusIcon(props) {
    return (
        <Base {...props}>
            <path d="M12 5v14" />
            <path d="M5 12h14" />
        </Base>
    );
}

export function SendIcon(props) {
    return (
        <Base {...props}>
            <path d="M4 12 20 4 12 20l-2-7-6-1Z" />
        </Base>
    );
}

export function CameraIcon(props) {
    return (
        <Base {...props}>
            <path d="M8.5 7.5 10 5h4l1.5 2.5H19a1 1 0 0 1 1 1V18a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V8.5a1 1 0 0 1 1-1h3.5Z" />
            <circle cx="12" cy="13" r="3.2" />
        </Base>
    );
}

export function MoreIcon(props) {
    return (
        <Base {...props}>
            <circle cx="5" cy="12" r="1.2" fill="currentColor" stroke="none" />
            <circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none" />
            <circle cx="19" cy="12" r="1.2" fill="currentColor" stroke="none" />
        </Base>
    );
}

export function CommentIcon(props) {
    return (
        <Base {...props}>
            <path d="M20 12.2a7.4 7.4 0 0 1-7.4 7.4H7.2L4 21.8v-4.4A7.4 7.4 0 0 1 11.4 4.8h1.2A7.4 7.4 0 0 1 20 12.2Z" />
        </Base>
    );
}

export function ShareIcon(props) {
    return (
        <Base {...props}>
            <path d="M14.5 6.5 19 11l-4.5 4.5" />
            <path d="M18.5 11H10a5.5 5.5 0 0 0-5.5 5.5V19" />
        </Base>
    );
}

export function HashIcon(props) {
    return (
        <Base {...props}>
            <path d="m9 4-2 16" />
            <path d="m17 4-2 16" />
            <path d="M5 9h14" />
            <path d="M4 15h14" />
        </Base>
    );
}

export function ChecklistIcon(props) {
    return (
        <Base {...props}>
            <path d="M9 6h11" />
            <path d="M9 12h11" />
            <path d="M9 18h11" />
            <path d="m4 6 1.2 1.2L7.5 5" />
            <path d="m4 12 1.2 1.2L7.5 11" />
            <path d="m4 18 1.2 1.2L7.5 17" />
        </Base>
    );
}
