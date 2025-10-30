import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import WelcomeStep from './Components/WelcomeStep';
import SetupStep from './Components/SetupStep';
import SortingStep from './Components/SortingStep';

interface SortingSession {
    id: number;
    source_folder_id: string;
    source_folder_name: string;
    destination_folders: Array<{
        id: string;
        name: string;
        parents?: string[];
    }>;
    images: Array<{
        id: string;
        name: string;
        mimeType: string;
        size: number;
        thumbnailLink?: string;
        webContentLink?: string;
        width?: number;
        height?: number;
    }>;
    total_images: number;
    sorted_images: number;
    remaining_images: number;
    current_image_index: number;
    status: 'setup' | 'active' | 'completed' | 'paused';
}

interface Props extends PageProps {
    session: SortingSession | null;
    googleDriveConnected: boolean;
    googleDriveEmail?: string;
}

export default function Index({ auth, session: initialSession, googleDriveConnected, googleDriveEmail }: Props) {
    const [session, setSession] = useState<SortingSession | null>(initialSession);
    const [currentStep, setCurrentStep] = useState<'welcome' | 'setup' | 'sorting'>('welcome');

    useEffect(() => {
        if (session) {
            if (session.status === 'setup') {
                setCurrentStep('setup');
            } else if (session.status === 'active' || session.status === 'paused') {
                setCurrentStep('sorting');
            } else {
                setCurrentStep('welcome');
            }
        } else {
            setCurrentStep('welcome');
        }
    }, [session]);

    const handleSessionCreated = (newSession: SortingSession) => {
        setSession(newSession);
        setCurrentStep('setup');
    };

    const handleStartSorting = () => {
        setCurrentStep('sorting');
    };

    const handleReset = () => {
        setSession(null);
        setCurrentStep('welcome');
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        EzSorting - Photo Organizer
                    </h2>
                    {googleDriveConnected && (
                        <div className="flex items-center text-sm">
                            <svg
                                className="w-4 h-4 text-green-600 mr-1"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fillRule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clipRule="evenodd"
                                />
                            </svg>
                            <span className="text-green-600 font-medium">
                                Drive: {googleDriveEmail || 'Connected'}
                            </span>
                        </div>
                    )}
                </div>
            }
        >
            <Head title="EzSorting" />

            <div className="py-6 sm:py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {currentStep === 'welcome' && (
                        <WelcomeStep
                            googleDriveConnected={googleDriveConnected}
                            googleDriveEmail={googleDriveEmail}
                            onSessionCreated={handleSessionCreated}
                        />
                    )}

                    {currentStep === 'setup' && session && (
                        <SetupStep
                            session={session}
                            onSessionUpdate={setSession}
                            onStartSorting={handleStartSorting}
                            onReset={handleReset}
                        />
                    )}

                    {currentStep === 'sorting' && session && (
                        <SortingStep
                            session={session}
                            onSessionUpdate={setSession}
                            onReset={handleReset}
                        />
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
