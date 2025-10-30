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
    googleConnected: boolean;
}

export default function Index({ auth, session: initialSession, googleConnected }: Props) {
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
                    {googleConnected && (
                        <span className="text-sm text-green-600 font-medium">
                            Google Drive Connected
                        </span>
                    )}
                </div>
            }
        >
            <Head title="EzSorting" />

            <div className="py-6 sm:py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {currentStep === 'welcome' && (
                        <WelcomeStep
                            googleConnected={googleConnected}
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
